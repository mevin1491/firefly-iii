<?php

declare(strict_types=1);

namespace FireflyIII\Services\Portfolio;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class FSMOneImporter
{
    private string $dateFormat;
    private string $delimiter;

    public function __construct()
    {
        $this->dateFormat = config('portfolio.fsmone.date_format', 'd/m/Y');
        $this->delimiter  = config('portfolio.fsmone.delimiter', ',');
    }

    public function parseHoldings(UploadedFile $file): array
    {
        $rows     = $this->readCsv($file);
        $holdings = [];

        foreach ($rows as $row) {
            $parsed = $this->parseHoldingRow($row);
            if (null !== $parsed) {
                $holdings[] = $parsed;
            }
        }

        Log::info(sprintf('FSMOne: parsed %d holdings from %s', count($holdings), $file->getClientOriginalName()));

        return $holdings;
    }

    public function parseTransactions(UploadedFile $file): array
    {
        $rows         = $this->readCsv($file);
        $transactions = [];

        foreach ($rows as $row) {
            $parsed = $this->parseTransactionRow($row);
            if (null !== $parsed) {
                $transactions[] = $parsed;
            }
        }

        Log::info(sprintf('FSMOne: parsed %d transactions from %s', count($transactions), $file->getClientOriginalName()));

        return $transactions;
    }

    private function readCsv(UploadedFile $file): array
    {
        $content = $file->getContent();
        $lines   = explode("\n", $content);
        $rows    = [];
        $headers = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            $fields = str_getcsv($line, $this->delimiter);

            if (null === $headers) {
                $headers = array_map(fn ($h) => strtolower(trim($h)), $fields);

                continue;
            }

            if (count($fields) === count($headers)) {
                $rows[] = array_combine($headers, $fields);
            }
        }

        return $rows;
    }

    private function parseHoldingRow(array $row): ?array
    {
        $fundName = $row['fund name'] ?? $row['fund'] ?? $row['name'] ?? null;
        $units    = $row['units'] ?? $row['unit'] ?? $row['quantity'] ?? null;
        $nav      = $row['nav'] ?? $row['price'] ?? $row['nav per unit'] ?? null;
        $cost     = $row['avg cost'] ?? $row['average cost'] ?? $row['cost'] ?? null;

        if (null === $fundName || null === $units) {
            return null;
        }

        $symbol   = $row['isin'] ?? $row['fund code'] ?? $row['code'] ?? $fundName;
        $currency = $row['currency'] ?? 'MYR';

        return [
            'symbol'              => $symbol,
            'name'                => $fundName,
            'asset_class'         => 'fund',
            'quantity'            => $this->cleanNumber($units),
            'average_cost'        => $this->cleanNumber($cost ?? '0'),
            'cost_currency_code'  => strtoupper(trim($currency)),
            'current_price'       => $this->cleanNumber($nav ?? '0'),
            'price_currency_code' => strtoupper(trim($currency)),
        ];
    }

    private function parseTransactionRow(array $row): ?array
    {
        $fundName = $row['fund name'] ?? $row['fund'] ?? $row['name'] ?? null;
        $type     = $row['type'] ?? $row['transaction type'] ?? $row['trans type'] ?? null;
        $amount   = $row['amount'] ?? $row['total'] ?? $row['total amount'] ?? null;
        $units    = $row['units'] ?? $row['unit'] ?? $row['quantity'] ?? null;
        $price    = $row['nav'] ?? $row['price'] ?? $row['nav per unit'] ?? null;
        $dateStr  = $row['date'] ?? $row['transaction date'] ?? $row['trans date'] ?? null;

        if (null === $fundName || null === $amount) {
            return null;
        }

        $symbol   = $row['isin'] ?? $row['fund code'] ?? $row['code'] ?? $fundName;
        $currency = $row['currency'] ?? 'MYR';
        $date     = null !== $dateStr ? $this->parseDate($dateStr) : now();

        return [
            'symbol'           => $symbol,
            'transaction_type' => $this->mapTransactionType($type ?? 'buy'),
            'quantity'         => $this->cleanNumber($units ?? '0'),
            'price_per_unit'   => $this->cleanNumber($price ?? '0'),
            'total_amount'     => $this->cleanNumber($amount),
            'currency_code'    => strtoupper(trim($currency)),
            'fees'             => $this->cleanNumber($row['fees'] ?? $row['fee'] ?? '0'),
            'transacted_at'    => $date,
            'external_id'      => $row['reference'] ?? $row['ref'] ?? $row['ref no'] ?? null,
            'notes'            => $row['remarks'] ?? $row['notes'] ?? null,
        ];
    }

    private function mapTransactionType(string $type): string
    {
        $type = strtolower(trim($type));

        return match (true) {
            str_contains($type, 'buy'), str_contains($type, 'subscription')   => 'buy',
            str_contains($type, 'sell'), str_contains($type, 'redemption')    => 'sell',
            str_contains($type, 'dividend'), str_contains($type, 'dist')      => 'dividend',
            str_contains($type, 'switch')                                     => 'buy',
            default                                                           => 'buy',
        };
    }

    private function cleanNumber(string $value): string
    {
        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);

        return '' === $cleaned ? '0' : $cleaned;
    }

    private function parseDate(string $dateStr): Carbon
    {
        try {
            return Carbon::createFromFormat($this->dateFormat, trim($dateStr));
        } catch (\Exception) {
            return Carbon::parse(trim($dateStr));
        }
    }
}
