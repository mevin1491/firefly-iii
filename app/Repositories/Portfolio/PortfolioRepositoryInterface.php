<?php

declare(strict_types=1);

namespace FireflyIII\Repositories\Portfolio;

use Carbon\Carbon;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Models\PortfolioHolding;
use FireflyIII\Models\PortfolioImportLog;
use FireflyIII\Models\PortfolioSnapshot;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

interface PortfolioRepositoryInterface
{
    public function setUser(null|Authenticatable|User $user): void;

    public function getAccounts(): Collection;

    public function getAccountById(int $id): ?PortfolioAccount;

    public function storeAccount(array $data): PortfolioAccount;

    public function updateAccount(PortfolioAccount $account, array $data): PortfolioAccount;

    public function destroyAccount(PortfolioAccount $account): bool;

    public function getHoldingsByAccount(PortfolioAccount $account): Collection;

    public function getAllHoldings(): Collection;

    public function upsertHolding(PortfolioAccount $account, array $data): PortfolioHolding;

    public function getTransactions(?PortfolioAccount $account, ?Carbon $start, ?Carbon $end): Collection;

    public function storeTransaction(PortfolioAccount $account, array $data): \FireflyIII\Models\PortfolioTransaction;

    public function storeTransactions(PortfolioAccount $account, array $rows): int;

    public function getLatestPrice(string $symbol, string $platform): ?\FireflyIII\Models\PortfolioPrice;

    public function storePrices(array $prices): int;

    public function getPriceHistory(string $symbol, string $platform, Carbon $start, Carbon $end): Collection;

    public function getSnapshots(Carbon $start, Carbon $end, ?int $accountId = null): Collection;

    public function storeSnapshot(array $data): PortfolioSnapshot;

    public function getTotalPortfolioValue(): array;

    public function getHoldingsBreakdown(): array;

    public function getRealizedGains(?Carbon $start, ?Carbon $end): array;

    public function getAssetAllocation(): array;

    public function storeImportLog(array $data): PortfolioImportLog;
}
