<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Portfolio;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Repositories\Portfolio\PortfolioRepositoryInterface;
use FireflyIII\Services\Portfolio\PortfolioSyncService;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    private PortfolioRepositoryInterface $repository;
    private PortfolioSyncService $syncService;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            $this->repository  = app(PortfolioRepositoryInterface::class);
            $this->repository->setUser(auth()->user());
            $this->syncService = app(PortfolioSyncService::class);

            return $next($request);
        });
    }

    public function sync(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->syncService->syncAll($user);

        return response()->json(['message' => 'Portfolio sync completed.']);
    }

    public function syncAccount(PortfolioAccount $portfolioAccount): JsonResponse
    {
        $this->syncService->syncAccount($portfolioAccount);

        return response()->json(['message' => sprintf('Sync completed for %s.', $portfolioAccount->name)]);
    }
}
