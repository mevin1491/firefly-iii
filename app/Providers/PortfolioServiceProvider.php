<?php

declare(strict_types=1);

namespace FireflyIII\Providers;

use FireflyIII\Repositories\Portfolio\PortfolioRepository;
use FireflyIII\Repositories\Portfolio\PortfolioRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

class PortfolioServiceProvider extends ServiceProvider
{
    public function boot(): void {}

    #[Override]
    public function register(): void
    {
        $this->app->bind(
            PortfolioRepositoryInterface::class,
            static function (Application $app): PortfolioRepositoryInterface {
                /** @var PortfolioRepositoryInterface $repository */
                $repository = app(PortfolioRepository::class);

                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }
}
