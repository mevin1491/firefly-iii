<?php

declare(strict_types=1);

namespace FireflyIII\Repositories\Portfolio;

use Carbon\Carbon;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Models\PortfolioHolding;
use FireflyIII\Models\PortfolioImportLog;
use FireflyIII\Models\PortfolioPrice;
use FireflyIII\Models\PortfolioSnapshot;
use FireflyIII\Models\PortfolioTransaction;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class PortfolioRepository implements PortfolioRepositoryInterface
{
    private ?User $user = null;

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function getAccounts(): Collection
    {
        return $this->user->portfolioAccounts()->where('active', true)->get();
    }

    public function getAccountById(int $id): ?PortfolioAccount
    {
        return $this->user->portfolioAccounts()->find($id);
    }

    public function storeAccount(array $data): PortfolioAccount
    {
        return PortfolioAccount::create([
            'user_id'       => $this->user->id,
            'user_group_id' => $this->user->user_group_id,
            'name'          => $data['name'],
            'platform'      => $data['platform'],
            'active'        => $data['active'] ?? true,
        ]);
    }

    public function updateAccount(PortfolioAccount $account, array $data): PortfolioAccount
    {
        $account->update($data);

        return $account->fresh();
    }

    public function destroyAccount(PortfolioAccount $account): bool
    {
        $account->delete();

        return true;
    }

    public function getHoldingsByAccount(PortfolioAccount $account): Collection
    {
        return $account->holdings()->where('quantity', '>', 0)->get();
    }

    public function getAllHoldings(): Collection
    {
        $accountIds = $this->user->portfolioAccounts()->where('active', true)->pluck('id');

        return PortfolioHolding::whereIn('portfolio_account_id', $accountIds)
            ->where('quantity', '>', 0)
            ->get();
    }

    public function upsertHolding(PortfolioAccount $account, array $data): PortfolioHolding
    {
        return PortfolioHolding::updateOrCreate(
            [
                'portfolio_account_id' => $account->id,
                'symbol'               => $data['symbol'],
            ],
            [
                'name'                => $data['name'],
                'asset_class'         => $data['asset_class'],
                'quantity'            => $data['quantity'],
                'average_cost'        => $data['average_cost'],
                'cost_currency_code'  => $data['cost_currency_code'],
                'current_price'       => $data['current_price'] ?? null,
                'price_currency_code' => $data['price_currency_code'],
                'current_value'       => $data['current_value'] ?? null,
                'unrealized_pnl'      => $data['unrealized_pnl'] ?? null,
                'last_price_update'   => $data['last_price_update'] ?? null,
            ]
        );
    }

    public function getTransactions(?PortfolioAccount $account, ?Carbon $start, ?Carbon $end): Collection
    {
        $query = PortfolioTransaction::query();

        if (null !== $account) {
            $query->where('portfolio_account_id', $account->id);
        } else {
            $accountIds = $this->user->portfolioAccounts()->pluck('id');
            $query->whereIn('portfolio_account_id', $accountIds);
        }

        if (null !== $start) {
            $query->where('transacted_at', '>=', $start);
        }
        if (null !== $end) {
            $query->where('transacted_at', '<=', $end);
        }

        return $query->orderBy('transacted_at', 'desc')->get();
    }

    public function storeTransaction(PortfolioAccount $account, array $data): PortfolioTransaction
    {
        return PortfolioTransaction::create(array_merge($data, [
            'portfolio_account_id' => $account->id,
        ]));
    }

    public function storeTransactions(PortfolioAccount $account, array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if (null !== ($row['external_id'] ?? null)) {
                $exists = PortfolioTransaction::where('portfolio_account_id', $account->id)
                    ->where('external_id', $row['external_id'])
                    ->exists();
                if ($exists) {
                    continue;
                }
            }
            PortfolioTransaction::create(array_merge($row, [
                'portfolio_account_id' => $account->id,
            ]));
            ++$count;
        }

        return $count;
    }

    public function getLatestPrice(string $symbol, string $platform): ?PortfolioPrice
    {
        return PortfolioPrice::where('symbol', $symbol)
            ->where('platform', $platform)
            ->orderBy('priced_at', 'desc')
            ->first();
    }

    public function storePrices(array $prices): int
    {
        $count = 0;
        foreach ($prices as $price) {
            PortfolioPrice::updateOrCreate(
                [
                    'symbol'   => $price['symbol'],
                    'platform' => $price['platform'],
                    'priced_at' => $price['priced_at'],
                ],
                [
                    'price'         => $price['price'],
                    'currency_code' => $price['currency_code'],
                ]
            );
            ++$count;
        }

        return $count;
    }

    public function getPriceHistory(string $symbol, string $platform, Carbon $start, Carbon $end): Collection
    {
        return PortfolioPrice::where('symbol', $symbol)
            ->where('platform', $platform)
            ->whereBetween('priced_at', [$start, $end])
            ->orderBy('priced_at')
            ->get();
    }

    public function getSnapshots(Carbon $start, Carbon $end, ?int $accountId = null): Collection
    {
        $query = PortfolioSnapshot::where('user_id', $this->user->id)
            ->whereBetween('snapshot_date', [$start, $end])
            ->orderBy('snapshot_date');

        if (null !== $accountId) {
            $query->where('portfolio_account_id', $accountId);
        } else {
            $query->whereNull('portfolio_account_id');
        }

        return $query->get();
    }

    public function storeSnapshot(array $data): PortfolioSnapshot
    {
        return PortfolioSnapshot::updateOrCreate(
            [
                'user_id'              => $this->user->id,
                'portfolio_account_id' => $data['portfolio_account_id'] ?? null,
                'snapshot_date'        => $data['snapshot_date'],
                'currency_code'        => $data['currency_code'],
            ],
            [
                'total_value' => $data['total_value'],
                'total_cost'  => $data['total_cost'],
            ]
        );
    }

    public function getTotalPortfolioValue(): array
    {
        $holdings = $this->getAllHoldings();
        $result   = [];

        foreach ($holdings as $holding) {
            $currency = $holding->price_currency_code;
            if (!isset($result[$currency])) {
                $result[$currency] = [
                    'currency_code'  => $currency,
                    'total_value'    => '0',
                    'total_cost'     => '0',
                    'unrealized_pnl' => '0',
                ];
            }
            $result[$currency]['total_value']    = bcadd($result[$currency]['total_value'], $holding->current_value ?? '0', 12);
            $result[$currency]['total_cost']     = bcadd($result[$currency]['total_cost'], bcmul($holding->quantity, $holding->average_cost, 12), 12);
            $result[$currency]['unrealized_pnl'] = bcadd($result[$currency]['unrealized_pnl'], $holding->unrealized_pnl ?? '0', 12);
        }

        return array_values($result);
    }

    public function getHoldingsBreakdown(): array
    {
        $accounts = $this->getAccounts();
        $result   = [];

        foreach ($accounts as $account) {
            $holdings = $this->getHoldingsByAccount($account);
            foreach ($holdings as $holding) {
                $result[] = [
                    'platform'        => $account->platform->value,
                    'account_name'    => $account->name,
                    'symbol'          => $holding->symbol,
                    'name'            => $holding->name,
                    'asset_class'     => $holding->asset_class->value,
                    'quantity'        => $holding->quantity,
                    'average_cost'    => $holding->average_cost,
                    'current_price'   => $holding->current_price,
                    'current_value'   => $holding->current_value,
                    'unrealized_pnl'  => $holding->unrealized_pnl,
                    'currency_code'   => $holding->price_currency_code,
                ];
            }
        }

        return $result;
    }

    public function getRealizedGains(?Carbon $start, ?Carbon $end): array
    {
        $transactions = $this->getTransactions(null, $start, $end);
        $gains        = [];

        foreach ($transactions as $txn) {
            if ('sell' === $txn->transaction_type->value) {
                $symbol = $txn->symbol;
                if (!isset($gains[$symbol])) {
                    $gains[$symbol] = [
                        'symbol'        => $symbol,
                        'total_sold'    => '0',
                        'total_fees'    => '0',
                        'currency_code' => $txn->currency_code,
                    ];
                }
                $gains[$symbol]['total_sold'] = bcadd($gains[$symbol]['total_sold'], $txn->total_amount, 12);
                $gains[$symbol]['total_fees'] = bcadd($gains[$symbol]['total_fees'], $txn->fees, 12);
            }
        }

        return array_values($gains);
    }

    public function getAssetAllocation(): array
    {
        $holdings   = $this->getAllHoldings();
        $byPlatform = [];
        $byClass    = [];
        $byCurrency = [];

        foreach ($holdings as $holding) {
            $account  = $holding->portfolioAccount;
            $platform = $account->platform->value;
            $class    = $holding->asset_class->value;
            $currency = $holding->price_currency_code;
            $value    = $holding->current_value ?? '0';

            $byPlatform[$platform] = bcadd($byPlatform[$platform] ?? '0', $value, 12);
            $byClass[$class]       = bcadd($byClass[$class] ?? '0', $value, 12);
            $byCurrency[$currency] = bcadd($byCurrency[$currency] ?? '0', $value, 12);
        }

        return [
            'by_platform' => $byPlatform,
            'by_class'    => $byClass,
            'by_currency' => $byCurrency,
        ];
    }

    public function storeImportLog(array $data): PortfolioImportLog
    {
        return PortfolioImportLog::create(array_merge($data, [
            'user_id' => $this->user->id,
        ]));
    }
}
