<?php

declare(strict_types=1);

namespace FireflyIII\Support\Cronjobs;

use Carbon\Carbon;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Services\Portfolio\PortfolioSyncService;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;

class PortfolioPriceCronjob extends AbstractCronjob
{
    public int $timeBetweenRuns = 3600;

    public function fire(): void
    {
        /** @var Configuration $config */
        $config        = FireflyConfig::get('last_portfolio_sync_job', 0);
        $lastTime      = (int) $config->data;
        $diff          = Carbon::now()->getTimestamp() - $lastTime;
        $diffForHumans = today(config('app.timezone'))->diffForHumans(Carbon::createFromTimestamp($lastTime), null, true);

        if (0 === $lastTime) {
            Log::info('Portfolio sync cron-job has never fired before.');
        }

        $interval = (int) config('portfolio.sync_interval', 3600);

        if ($lastTime > 0 && $diff <= $interval) {
            Log::info(sprintf('It has been %s since the portfolio sync cron-job has fired.', $diffForHumans));
            if (false === $this->force) {
                Log::info('The portfolio sync cron-job will not fire now.');
                $this->message = sprintf('It has been %s since the portfolio sync cron-job has fired. It will not fire now.', $diffForHumans);

                return;
            }
            Log::info('Execution of the portfolio sync cron-job has been FORCED.');
        }

        if ($lastTime > 0 && $diff > $interval) {
            Log::info(sprintf('It has been %s since the portfolio sync cron-job has fired. It will fire now!', $diffForHumans));
        }

        $this->firePortfolioSync();
        app('preferences')->mark();
    }

    private function firePortfolioSync(): void
    {
        Log::info(sprintf('Will now fire portfolio sync cron job task for date "%s".', $this->date->format('Y-m-d')));

        $syncService = app(PortfolioSyncService::class);

        $userIds = PortfolioAccount::where('active', true)->distinct()->pluck('user_id');
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (null !== $user) {
                $syncService->syncAll($user);
            }
        }

        $this->jobFired     = true;
        $this->jobErrored   = false;
        $this->jobSucceeded = true;
        $this->message      = 'Portfolio sync cron job fired successfully.';

        FireflyConfig::set('last_portfolio_sync_job', (int) $this->date->format('U'));
        Log::info('Done with portfolio sync job task.');
    }
}
