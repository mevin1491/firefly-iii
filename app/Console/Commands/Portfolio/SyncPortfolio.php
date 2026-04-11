<?php

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Portfolio;

use Carbon\Carbon;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Models\PortfolioHolding;
use FireflyIII\Models\PortfolioSnapshot;
use FireflyIII\Services\Portfolio\LunoService;
use Illuminate\Console\Command;

class SyncPortfolio extends Command
{
    use ShowsFriendlyMessages;

    protected $signature = 'portfolio:sync
                            {--user= : Only sync for a specific user ID}
                            {--platform= : Only sync a specific platform (luno)}
                            {--snapshot : Also record a daily snapshot after syncing}';

    protected $description = 'Sync portfolio data from connected platforms (Luno API). Designed for Synology NAS cron jobs.';

    public function handle(): int
    {
        $this->friendlyInfo('Starting portfolio sync...');

        $query = PortfolioAccount::where('active', true);

        if ($this->option('user')) {
            $query->where('user_id', (int) $this->option('user'));
        }

        if ($this->option('platform')) {
            $query->where('platform', $this->option('platform'));
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->friendlyWarning('No active portfolio accounts found.');

            return 0;
        }

        $this->friendlyInfo(sprintf('Found %d active portfolio account(s).', $accounts->count()));

        foreach ($accounts as $account) {
            $this->syncAccount($account);
        }

        $this->friendlyPositive('Portfolio sync complete.');

        return 0;
    }

    private function syncAccount(PortfolioAccount $account): void
    {
        $label = sprintf('[%s] %s (user #%d)', ucfirst($account->platform), $account->name, $account->user_id);

        if ('luno' === $account->platform) {
            $this->friendlyInfo("Syncing {$label} via Luno API...");

            if (empty($account->api_key) || empty($account->api_secret)) {
                $this->friendlyWarning("Skipping {$label}: no API credentials configured.");

                return;
            }

            $service = new LunoService($account);

            // Sync balances
            $log = $service->syncBalances();
            if ('success' === $log->status) {
                $this->friendlyPositive("  Balances: {$log->message}");
            } else {
                $this->friendlyError("  Balances error: {$log->message}");
            }

            // Sync transactions
            $txLog = $service->syncTransactions();
            if ('success' === $txLog->status) {
                $this->friendlyPositive("  Transactions: {$txLog->message}");
            } else {
                $this->friendlyError("  Transactions error: {$txLog->message}");
            }

            // Record snapshot if requested
            if ($this->option('snapshot')) {
                $this->recordSnapshot($account);
            }
        } else {
            $this->friendlyInfo("Skipping {$label}: platform '{$account->platform}' requires manual CSV import.");
        }
    }

    private function recordSnapshot(PortfolioAccount $account): void
    {
        $today      = Carbon::today();
        $totalValue = (float) PortfolioHolding::where('portfolio_account_id', $account->id)->sum('market_value');
        $totalCost  = (float) PortfolioHolding::where('portfolio_account_id', $account->id)->sum('cost_basis');
        $totalPnl   = $totalValue - $totalCost;

        // Get yesterday's snapshot for day change calculation
        $yesterday  = PortfolioSnapshot::where('portfolio_account_id', $account->id)
            ->where('snapshot_date', $today->copy()->subDay()->toDateString())
            ->first();

        $dayChange    = $yesterday ? $totalValue - (float) $yesterday->total_value : 0;
        $dayChangePct = $yesterday && (float) $yesterday->total_value > 0
            ? ($dayChange / (float) $yesterday->total_value) * 100
            : 0;

        PortfolioSnapshot::updateOrCreate(
            [
                'portfolio_account_id' => $account->id,
                'snapshot_date'        => $today->toDateString(),
            ],
            [
                'total_value'    => $totalValue,
                'total_cost'     => $totalCost,
                'total_pnl'      => $totalPnl,
                'day_change'     => $dayChange,
                'day_change_pct' => $dayChangePct,
            ]
        );

        $this->friendlyPositive(sprintf('  Snapshot recorded: value=%.2f, change=%.2f (%.2f%%)', $totalValue, $dayChange, $dayChangePct));
    }
}
