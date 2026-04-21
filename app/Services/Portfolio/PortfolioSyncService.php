<?php

declare(strict_types=1);

namespace FireflyIII\Services\Portfolio;

use Carbon\Carbon;
use FireflyIII\Enums\PortfolioPlatformEnum;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Repositories\Portfolio\PortfolioRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;

class PortfolioSyncService
{
    private PortfolioRepositoryInterface $repository;
    private PortfolioCalculator $calculator;

    public function __construct(PortfolioRepositoryInterface $repository, PortfolioCalculator $calculator)
    {
        $this->repository = $repository;
        $this->calculator = $calculator;
    }

    public function syncAll(User $user): void
    {
        $this->repository->setUser($user);
        $accounts = $this->repository->getAccounts();

        foreach ($accounts as $account) {
            $this->syncAccount($account);
        }

        $this->takeSnapshot($user);
        Log::info(sprintf('Portfolio sync completed for user %d', $user->id));
    }

    public function syncAccount(PortfolioAccount $account): void
    {
        Log::info(sprintf('Syncing portfolio account: %s (%s)', $account->name, $account->platform->value));

        match ($account->platform) {
            PortfolioPlatformEnum::MOOMOO => $this->syncMoomoo($account),
            PortfolioPlatformEnum::LUNO   => $this->syncLuno($account),
            PortfolioPlatformEnum::FSMONE => Log::info('FSMOne accounts are updated via CSV import only.'),
        };

        $account->update(['last_synced_at' => Carbon::now()]);
    }

    public function syncMoomoo(PortfolioAccount $account): void
    {
        $client = app(MoomooClient::class);

        if (!$client->isAvailable()) {
            Log::warning('Moomoo OpenD gateway is not available.');

            return;
        }

        $positions = $client->getPositions();
        $now       = Carbon::now();

        foreach ($positions as $position) {
            $position['last_price_update'] = $now;
            $position['current_value']     = bcmul($position['quantity'], $position['current_price'] ?? '0', 12);
            $position['unrealized_pnl']    = $position['unrealized_pnl'] ?? $this->calculator->calculateUnrealizedPnl(
                new \FireflyIII\Models\PortfolioHolding($position)
            );
            $this->repository->upsertHolding($account, $position);

            $this->repository->storePrices([[
                'symbol'        => $position['symbol'],
                'platform'      => 'moomoo',
                'price'         => $position['current_price'] ?? '0',
                'currency_code' => $position['price_currency_code'],
                'priced_at'     => $now->toDateString(),
            ]]);
        }

        Log::info(sprintf('Moomoo: synced %d positions for account %s', count($positions), $account->name));
    }

    public function syncLuno(PortfolioAccount $account): void
    {
        $client   = app(LunoClient::class);
        $balances = $client->getBalances();
        $tickers  = $client->getAllTickers();

        $tickerMap = [];
        foreach ($tickers as $ticker) {
            $tickerMap[$ticker['pair'] ?? ''] = $ticker;
        }

        $now = Carbon::now();

        foreach ($balances as $balance) {
            $symbol = $balance['symbol'];
            $pair   = $symbol . 'ZAR';
            $ticker = $tickerMap[$pair] ?? ($tickerMap[$symbol . 'USDC'] ?? null);

            $currentPrice = null !== $ticker ? ($ticker['last_trade'] ?? '0') : null;
            $priceCurrency = null !== $ticker ? $this->extractQuoteCurrency($ticker['pair'] ?? '') : $symbol;

            $holdingData = [
                'symbol'              => $symbol,
                'name'                => $balance['name'],
                'asset_class'         => 'crypto',
                'quantity'            => $balance['quantity'],
                'average_cost'        => '0',
                'cost_currency_code'  => $priceCurrency,
                'current_price'       => $currentPrice,
                'price_currency_code' => $priceCurrency,
                'current_value'       => null !== $currentPrice ? bcmul($balance['quantity'], $currentPrice, 12) : null,
                'unrealized_pnl'      => null,
                'last_price_update'   => $now,
            ];

            $this->repository->upsertHolding($account, $holdingData);

            if (null !== $currentPrice) {
                $this->repository->storePrices([[
                    'symbol'        => $symbol,
                    'platform'      => 'luno',
                    'price'         => $currentPrice,
                    'currency_code' => $priceCurrency,
                    'priced_at'     => $now->toDateString(),
                ]]);
            }
        }

        Log::info(sprintf('Luno: synced %d balances for account %s', count($balances), $account->name));
    }

    public function recalculateHoldings(PortfolioAccount $account): void
    {
        $holdings = $this->repository->getHoldingsByAccount($account);
        foreach ($holdings as $holding) {
            $currentValue  = $this->calculator->calculateCurrentValue($holding);
            $unrealizedPnl = $this->calculator->calculateUnrealizedPnl($holding);
            $holding->update([
                'current_value'  => $currentValue,
                'unrealized_pnl' => $unrealizedPnl,
            ]);
        }
    }

    public function takeSnapshot(User $user): void
    {
        $this->repository->setUser($user);
        $totals = $this->repository->getTotalPortfolioValue();
        $today  = Carbon::today()->toDateString();

        foreach ($totals as $total) {
            $this->repository->storeSnapshot([
                'portfolio_account_id' => null,
                'total_value'          => $total['total_value'],
                'total_cost'           => $total['total_cost'],
                'currency_code'        => $total['currency_code'],
                'snapshot_date'        => $today,
            ]);
        }

        $accounts = $this->repository->getAccounts();
        foreach ($accounts as $account) {
            $accountHoldings = $this->repository->getHoldingsByAccount($account);
            $byCurrency      = [];
            foreach ($accountHoldings as $holding) {
                $currency = $holding->price_currency_code;
                if (!isset($byCurrency[$currency])) {
                    $byCurrency[$currency] = ['value' => '0', 'cost' => '0'];
                }
                $byCurrency[$currency]['value'] = bcadd($byCurrency[$currency]['value'], $holding->current_value ?? '0', 12);
                $byCurrency[$currency]['cost']  = bcadd($byCurrency[$currency]['cost'], bcmul($holding->quantity, $holding->average_cost, 12), 12);
            }

            foreach ($byCurrency as $currency => $amounts) {
                $this->repository->storeSnapshot([
                    'portfolio_account_id' => $account->id,
                    'total_value'          => $amounts['value'],
                    'total_cost'           => $amounts['cost'],
                    'currency_code'        => $currency,
                    'snapshot_date'        => $today,
                ]);
            }
        }
    }

    private function extractQuoteCurrency(string $pair): string
    {
        $fiatCurrencies = ['ZAR', 'USD', 'EUR', 'GBP', 'NGN', 'MYR', 'SGD', 'IDR', 'USDC', 'USDT'];
        foreach ($fiatCurrencies as $fiat) {
            if (str_ends_with($pair, $fiat)) {
                return $fiat;
            }
        }

        return 'ZAR';
    }
}
