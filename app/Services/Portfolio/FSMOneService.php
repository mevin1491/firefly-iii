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
 * FSMOne (iFAST Financial) portfolio service.
 *
 * FSMOne has no public API. This service imports data from CSV/XLS exports
 * that users download from the FSMOne web portal.
 *
 * Supported exports:
 * - Portfolio Holdings export
 * - Transaction History export
 */
class FSMOneService
{
    private PortfolioAccount $account;

    public function __construct(PortfolioAccount $account)
    {
        $this->account = $account;
    }

    /**
     * Import portfolio holdings from FSMOne CSV export.
     *
     * Typical FSMOne holdings export columns:
     *  Fund Name, Fund Code/ISIN, Units, NAV Price, Market Value, Cost, Gain/Loss, Gain/Loss%
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

                $fundName = $this->findField($mapped, [
                    'fund_name', 'fund', 'name', 'security_name', 'product_name',
                ]);
                $symbol   = $this->findField($mapped, [
                    'fund_code', 'isin', 'code', 'symbol', 'product_code', 'isin_code',
                ]);

                if (empty($fundName) && empty($symbol)) {
                    continue;
                }
                if (empty($symbol)) {
                    $symbol = substr(preg_replace('/[^A-Za-z0-9]/', '', $fundName), 0, 20);
                }

                $units      = $this->parseNumber($this->findField($mapped, ['units', 'unit', 'quantity', 'holdings', 'balance_units']));
                $navPrice   = $this->parseNumber($this->findField($mapped, ['nav_price', 'nav', 'price', 'unit_price', 'latest_nav']));
                $mktValue   = $this->parseNumber($this->findField($mapped, ['market_value', 'mkt_value', 'value', 'current_value']));
                $cost       = $this->parseNumber($this->findField($mapped, ['cost', 'total_cost', 'invested_amount', 'investment_amount']));
                $gainLoss   = $this->parseNumber($this->findField($mapped, ['gain_loss', 'gain', 'pnl', 'profit_loss', 'unrealised_gain_loss']));
                $gainLossPct = $this->parseNumber($this->findField($mapped, ['gain_loss_pct', 'gain_pct', 'return_pct', 'pnl_pct']));
                $currency   = $this->findField($mapped, ['currency', 'ccy', 'fund_currency']) ?: $this->account->currency;

                // Calculate missing values
                if ($mktValue <= 0 && $units > 0 && $navPrice > 0) {
                    $mktValue = $units * $navPrice;
                }
                if ($cost <= 0 && $units > 0 && $navPrice > 0) {
                    $cost = $mktValue; // assume cost = current value if not provided
                }
                if (0.0 === $gainLoss && $mktValue > 0 && $cost > 0) {
                    $gainLoss = $mktValue - $cost;
                }
                if (0.0 === $gainLossPct && $cost > 0) {
                    $gainLossPct = ($gainLoss / $cost) * 100;
                }

                $avgCost = $units > 0 ? $cost / $units : 0;

                PortfolioHolding::updateOrCreate(
                    [
                        'portfolio_account_id' => $this->account->id,
                        'symbol'               => $symbol,
                    ],
                    [
                        'name'              => $fundName ?: $symbol,
                        'asset_type'        => $this->detectAssetType($fundName, $symbol),
                        'quantity'          => $units,
                        'avg_cost'          => $avgCost,
                        'current_price'     => $navPrice,
                        'market_value'      => $mktValue,
                        'cost_basis'        => $cost,
                        'unrealized_pnl'    => $gainLoss,
                        'unrealized_pnl_pct' => $gainLossPct,
                        'currency'          => strtoupper($currency),
                    ]
                );

                ++$synced;
            }

            $log->records_synced = $synced;
            $log->message        = sprintf('Imported %d holdings from FSMOne CSV', $synced);

            $this->account->last_synced_at = Carbon::now();
            $this->account->save();
        } catch (\Exception $e) {
            Log::error('FSMOne holdings CSV import error: ' . $e->getMessage());
            $log->status  = 'error';
            $log->message = 'Import error: ' . $e->getMessage();
        }

        $log->save();

        return $log;
    }

    /**
     * Import transaction history from FSMOne CSV export.
     *
     * Typical columns:
     *  Date, Fund Name, Fund Code, Transaction Type, Units, NAV Price, Amount, Fees
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

                $dateStr  = $this->findField($mapped, ['date', 'transaction_date', 'trade_date', 'effective_date']);
                $fundName = $this->findField($mapped, ['fund_name', 'fund', 'name', 'product_name']);
                $symbol   = $this->findField($mapped, ['fund_code', 'isin', 'code', 'product_code']);

                if (empty($dateStr) || (empty($fundName) && empty($symbol))) {
                    continue;
                }

                if (empty($symbol)) {
                    $symbol = substr(preg_replace('/[^A-Za-z0-9]/', '', $fundName), 0, 20);
                }

                $txType   = strtolower($this->findField($mapped, [
                    'transaction_type', 'type', 'action', 'order_type',
                ]));
                $type     = $this->mapFsmTransactionType($txType);

                $units    = $this->parseNumber($this->findField($mapped, ['units', 'unit', 'quantity']));
                $price    = $this->parseNumber($this->findField($mapped, ['nav_price', 'nav', 'price', 'unit_price']));
                $amount   = $this->parseNumber($this->findField($mapped, ['amount', 'total', 'gross_amount', 'net_amount']));
                $fee      = $this->parseNumber($this->findField($mapped, ['fee', 'fees', 'sales_charge', 'commission', 'platform_fee']));
                $currency = $this->findField($mapped, ['currency', 'ccy']) ?: $this->account->currency;

                if ($amount <= 0 && $units > 0 && $price > 0) {
                    $amount = $units * $price;
                }

                $transactedAt = $this->parseDate($dateStr);
                $externalId   = md5($symbol . $dateStr . $type . $units . $price);

                $exists = PortfolioTransaction::where('external_id', $externalId)
                    ->where('portfolio_account_id', $this->account->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                PortfolioTransaction::create([
                    'portfolio_account_id' => $this->account->id,
                    'symbol'               => $symbol,
                    'name'                 => $fundName ?: $symbol,
                    'type'                 => $type,
                    'quantity'             => $units,
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
            $log->message        = sprintf('Imported %d transactions from FSMOne CSV', $synced);
        } catch (\Exception $e) {
            Log::error('FSMOne transaction CSV import error: ' . $e->getMessage());
            $log->status  = 'error';
            $log->message = 'Import error: ' . $e->getMessage();
        }

        $log->save();

        return $log;
    }

    private function mapFsmTransactionType(string $type): string
    {
        if (str_contains($type, 'buy') || str_contains($type, 'subscription') || str_contains($type, 'purchase')) {
            return 'buy';
        }
        if (str_contains($type, 'sell') || str_contains($type, 'redemption')) {
            return 'sell';
        }
        if (str_contains($type, 'dividend') || str_contains($type, 'distribution')) {
            return 'dividend';
        }
        if (str_contains($type, 'switch')) {
            return 'sell'; // treat switch-out as sell
        }

        return 'buy';
    }

    private function detectAssetType(string $name, string $code): string
    {
        $nameL = strtolower($name . ' ' . $code);

        if (str_contains($nameL, 'bond') || str_contains($nameL, 'fixed income') || str_contains($nameL, 'sukuk')) {
            return 'bond';
        }
        if (str_contains($nameL, 'etf')) {
            return 'etf';
        }
        if (str_contains($nameL, 'reit') || str_contains($nameL, 'property')) {
            return 'reit';
        }
        if (str_contains($nameL, 'money market') || str_contains($nameL, 'mmf')) {
            return 'fund';
        }

        return 'fund';
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
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd M Y', 'd/m/Y H:i:s', 'Y-m-d H:i:s'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($dateStr));
            } catch (\Exception) {
                continue;
            }
        }

        return Carbon::parse($dateStr);
    }
}
