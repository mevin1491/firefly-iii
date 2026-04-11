<?php

declare(strict_types=1);

namespace FireflyIII\Services\Portfolio;

use Carbon\Carbon;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Models\PortfolioHolding;
use FireflyIII\Models\PortfolioSyncLog;
use FireflyIII\Models\PortfolioTransaction;
use Illuminate\Support\Facades\Log;

/**
 * Moomoo (Futu) portfolio service.
 *
 * Moomoo uses the FutuOpenD daemon with TCP/protobuf, which isn't practical
 * for a PHP web app. This service supports two import methods:
 *
 * 1. CSV Import — Export from Moomoo app's "Trade History" / "Positions" screen
 * 2. JSON bridge — If you run a Python bridge script that fetches from FutuOpenD
 *    and writes JSON files, this service can read them.
 */
class MoomooService
{
    private PortfolioAccount $account;

    public function __construct(PortfolioAccount $account)
    {
        $this->account = $account;
    }

    /**
     * Import holdings from a Moomoo positions CSV export.
     *
     * Expected columns (flexible matching):
     *  Symbol/Code, Name, Qty/Quantity, Avg Cost/Cost Price, Market Price/Last Price,
     *  Market Value, Cost, P/L, P/L%, Currency
     */
    public function importHoldingsCsv(string $csvContent): PortfolioSyncLog
    {
        $log = new PortfolioSyncLog([
            'portfolio_account_id' => $this->account->id,
            'status'               => 'success',
            'records_synced'       => 0,
        ]);

        try {
            $lines = $this->parseCsv($csvContent);

            if (count($lines) < 2) {
                $log->status  = 'error';
                $log->message = 'CSV file appears to be empty or has no data rows.';
                $log->save();

                return $log;
            }

            $headers  = $this->normalizeHeaders($lines[0]);
            $synced   = 0;

            for ($i = 1, $iMax = count($lines); $i < $iMax; ++$i) {
                $row = $lines[$i];
                if (count($row) < count($headers)) {
                    continue;
                }

                $mapped = array_combine($headers, array_slice($row, 0, count($headers)));
                if (false === $mapped) {
                    continue;
                }

                $symbol = $this->findField($mapped, ['symbol', 'code', 'ticker', 'stock_code']);
                if (empty($symbol)) {
                    continue;
                }

                $name        = $this->findField($mapped, ['name', 'stock_name', 'security_name']) ?: $symbol;
                $qty         = $this->parseNumber($this->findField($mapped, ['qty', 'quantity', 'shares', 'position_qty']));
                $avgCost     = $this->parseNumber($this->findField($mapped, ['avg_cost', 'cost_price', 'average_cost', 'avg_price']));
                $mktPrice    = $this->parseNumber($this->findField($mapped, ['market_price', 'last_price', 'current_price', 'close_price', 'price']));
                $mktValue    = $this->parseNumber($this->findField($mapped, ['market_value', 'mkt_value', 'mkt_val']));
                $costBasis   = $this->parseNumber($this->findField($mapped, ['cost', 'cost_basis', 'total_cost']));
                $pnl         = $this->parseNumber($this->findField($mapped, ['pl', 'p_l', 'pnl', 'unrealized_pl', 'profit_loss']));
                $pnlPct      = $this->parseNumber($this->findField($mapped, ['pl_pct', 'p_l_pct', 'pnl_pct', 'pl_ratio']));
                $currency    = $this->findField($mapped, ['currency', 'ccy']) ?: $this->account->currency;

                // Calculate missing values
                if ($mktValue <= 0 && $qty > 0 && $mktPrice > 0) {
                    $mktValue = $qty * $mktPrice;
                }
                if ($costBasis <= 0 && $qty > 0 && $avgCost > 0) {
                    $costBasis = $qty * $avgCost;
                }
                if (0.0 === $pnl && $mktValue > 0 && $costBasis > 0) {
                    $pnl = $mktValue - $costBasis;
                }
                if (0.0 === $pnlPct && $costBasis > 0) {
                    $pnlPct = ($pnl / $costBasis) * 100;
                }

                $assetType = $this->detectAssetType($symbol, $name);

                PortfolioHolding::updateOrCreate(
                    [
                        'portfolio_account_id' => $this->account->id,
                        'symbol'               => $symbol,
                    ],
                    [
                        'name'              => $name,
                        'asset_type'        => $assetType,
                        'quantity'          => $qty,
                        'avg_cost'          => $avgCost,
                        'current_price'     => $mktPrice,
                        'market_value'      => $mktValue,
                        'cost_basis'        => $costBasis,
                        'unrealized_pnl'    => $pnl,
                        'unrealized_pnl_pct' => $pnlPct,
                        'currency'          => strtoupper($currency),
                    ]
                );

                ++$synced;
            }

            $log->records_synced = $synced;
            $log->message        = sprintf('Imported %d holdings from Moomoo CSV', $synced);

            $this->account->last_synced_at = Carbon::now();
            $this->account->save();
        } catch (\Exception $e) {
            Log::error('Moomoo CSV import error: ' . $e->getMessage());
            $log->status  = 'error';
            $log->message = 'Import error: ' . $e->getMessage();
        }

        $log->save();

        return $log;
    }

    /**
     * Import transactions from a Moomoo trade history CSV.
     *
     * Expected columns:
     *  Date, Symbol, Name, Side/Type (Buy/Sell), Qty, Price, Amount, Fee, Currency
     */
    public function importTransactionsCsv(string $csvContent): PortfolioSyncLog
    {
        $log = new PortfolioSyncLog([
            'portfolio_account_id' => $this->account->id,
            'status'               => 'success',
            'records_synced'       => 0,
        ]);

        try {
            $lines = $this->parseCsv($csvContent);

            if (count($lines) < 2) {
                $log->status  = 'error';
                $log->message = 'CSV file appears to be empty or has no data rows.';
                $log->save();

                return $log;
            }

            $headers = $this->normalizeHeaders($lines[0]);
            $synced  = 0;

            for ($i = 1, $iMax = count($lines); $i < $iMax; ++$i) {
                $row = $lines[$i];
                if (count($row) < count($headers)) {
                    continue;
                }

                $mapped = array_combine($headers, array_slice($row, 0, count($headers)));
                if (false === $mapped) {
                    continue;
                }

                $symbol  = $this->findField($mapped, ['symbol', 'code', 'ticker', 'stock_code']);
                $dateStr = $this->findField($mapped, ['date', 'trade_date', 'time', 'datetime', 'transaction_date']);
                if (empty($symbol) || empty($dateStr)) {
                    continue;
                }

                $name     = $this->findField($mapped, ['name', 'stock_name', 'security_name']) ?: $symbol;
                $side     = strtolower($this->findField($mapped, ['side', 'type', 'action', 'transaction_type', 'direction']));
                $type     = str_contains($side, 'sell') ? 'sell' : 'buy';
                if (str_contains($side, 'dividend')) {
                    $type = 'dividend';
                }

                $qty      = $this->parseNumber($this->findField($mapped, ['qty', 'quantity', 'shares', 'filled_qty']));
                $price    = $this->parseNumber($this->findField($mapped, ['price', 'filled_price', 'avg_price', 'trade_price']));
                $amount   = $this->parseNumber($this->findField($mapped, ['amount', 'total', 'gross_amount', 'trade_amount']));
                $fee      = $this->parseNumber($this->findField($mapped, ['fee', 'commission', 'fees', 'total_fee']));
                $currency = $this->findField($mapped, ['currency', 'ccy']) ?: $this->account->currency;

                if ($amount <= 0 && $qty > 0 && $price > 0) {
                    $amount = $qty * $price;
                }

                $transactedAt = $this->parseDate($dateStr);
                $externalId   = md5($symbol . $dateStr . $type . $qty . $price);

                $exists = PortfolioTransaction::where('external_id', $externalId)
                    ->where('portfolio_account_id', $this->account->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                PortfolioTransaction::create([
                    'portfolio_account_id' => $this->account->id,
                    'symbol'               => $symbol,
                    'name'                 => $name,
                    'type'                 => $type,
                    'quantity'             => $qty,
                    'price'                => $price,
                    'amount'               => $amount,
                    'fee'                  => $fee,
                    'currency'             => strtoupper($currency),
                    'external_id'          => $externalId,
                    'transacted_at'        => $transactedAt,
                ]);

                ++$synced;
            }

            $log->records_synced = $synced;
            $log->message        = sprintf('Imported %d transactions from Moomoo CSV', $synced);
        } catch (\Exception $e) {
            Log::error('Moomoo transaction CSV import error: ' . $e->getMessage());
            $log->status  = 'error';
            $log->message = 'Import error: ' . $e->getMessage();
        }

        $log->save();

        return $log;
    }

    /**
     * Import from FutuOpenD bridge JSON file.
     * This expects JSON output from a Python bridge script that queries FutuOpenD.
     */
    public function importBridgeJson(string $jsonContent): PortfolioSyncLog
    {
        $log = new PortfolioSyncLog([
            'portfolio_account_id' => $this->account->id,
            'status'               => 'success',
            'records_synced'       => 0,
        ]);

        try {
            $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);

            $synced = 0;

            foreach ($data['positions'] ?? [] as $pos) {
                $symbol = $pos['code'] ?? $pos['symbol'] ?? '';
                if (empty($symbol)) {
                    continue;
                }

                PortfolioHolding::updateOrCreate(
                    [
                        'portfolio_account_id' => $this->account->id,
                        'symbol'               => $symbol,
                    ],
                    [
                        'name'              => $pos['stock_name'] ?? $pos['name'] ?? $symbol,
                        'asset_type'        => $this->detectAssetType($symbol, $pos['stock_name'] ?? ''),
                        'quantity'          => (float) ($pos['qty'] ?? 0),
                        'avg_cost'          => (float) ($pos['cost_price'] ?? 0),
                        'current_price'     => (float) ($pos['market_val'] ?? 0) / max((float) ($pos['qty'] ?? 1), 1),
                        'market_value'      => (float) ($pos['market_val'] ?? 0),
                        'cost_basis'        => (float) ($pos['cost_price'] ?? 0) * (float) ($pos['qty'] ?? 0),
                        'unrealized_pnl'    => (float) ($pos['pl_val'] ?? 0),
                        'unrealized_pnl_pct' => (float) ($pos['pl_ratio'] ?? 0) * 100,
                        'currency'          => strtoupper($pos['currency'] ?? $this->account->currency),
                    ]
                );

                ++$synced;
            }

            $log->records_synced = $synced;
            $log->message        = sprintf('Imported %d positions from FutuOpenD bridge', $synced);

            $this->account->last_synced_at = Carbon::now();
            $this->account->save();
        } catch (\JsonException $e) {
            Log::error('Moomoo bridge JSON parse error: ' . $e->getMessage());
            $log->status  = 'error';
            $log->message = 'JSON parse error: ' . $e->getMessage();
        }

        $log->save();

        return $log;
    }

    private function parseCsv(string $content): array
    {
        $lines  = [];
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        while (false !== ($row = fgetcsv($handle))) {
            if (count($row) > 1 || (1 === count($row) && '' !== $row[0])) {
                $lines[] = $row;
            }
        }

        fclose($handle);

        return $lines;
    }

    /**
     * @return string[]
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(static function (string $h): string {
            $h = strtolower(trim($h));
            $h = preg_replace('/[^a-z0-9]+/', '_', $h);

            return trim($h, '_');
        }, $headers);
    }

    private function findField(array $mapped, array $possibleKeys): string
    {
        foreach ($possibleKeys as $key) {
            if (isset($mapped[$key]) && '' !== trim($mapped[$key])) {
                return trim($mapped[$key]);
            }
        }

        return '';
    }

    private function parseNumber(string $value): float
    {
        $value = str_replace([',', ' ', '%'], '', $value);
        $value = preg_replace('/[^0-9.\-]/', '', $value);

        return (float) $value;
    }

    private function parseDate(string $dateStr): Carbon
    {
        $formats = ['Y-m-d H:i:s', 'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d', 'd/m/Y H:i:s'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($dateStr));
            } catch (\Exception) {
                continue;
            }
        }

        return Carbon::parse($dateStr);
    }

    private function detectAssetType(string $symbol, string $name): string
    {
        $nameL   = strtolower($name);
        $symbolL = strtolower($symbol);

        if (str_contains($nameL, 'etf') || str_contains($symbolL, 'etf')) {
            return 'etf';
        }
        if (str_contains($nameL, 'bond') || str_contains($nameL, 'treasury')) {
            return 'bond';
        }
        if (str_contains($nameL, 'reit')) {
            return 'reit';
        }

        return 'stock';
    }
}
