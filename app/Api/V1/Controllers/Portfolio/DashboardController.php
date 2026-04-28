<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Portfolio;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Repositories\Portfolio\PortfolioRepositoryInterface;
use FireflyIII\Services\Portfolio\PortfolioCalculator;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    private PortfolioRepositoryInterface $repository;
    private PortfolioCalculator $calculator;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            $this->repository = app(PortfolioRepositoryInterface::class);
            $this->repository->setUser(auth()->user());
            $this->calculator = app(PortfolioCalculator::class);

            return $next($request);
        });
    }

    public function summary(): JsonResponse
    {
        $totals   = $this->repository->getTotalPortfolioValue();
        $accounts = $this->repository->getAccounts();

        return response()->json([
            'data' => [
                'totals'        => $totals,
                'account_count' => $accounts->count(),
            ],
        ]);
    }

    public function valueOverTime(): JsonResponse
    {
        $start = $this->parameters->get('start') ?? Carbon::now()->subMonths(6);
        $end   = $this->parameters->get('end') ?? Carbon::now();

        $snapshots = $this->repository->getSnapshots($start, $end);
        $data      = [];

        foreach ($snapshots as $snapshot) {
            $data[] = [
                'date'          => $snapshot->snapshot_date->format('Y-m-d'),
                'total_value'   => $snapshot->total_value,
                'total_cost'    => $snapshot->total_cost,
                'currency_code' => $snapshot->currency_code,
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function holdingsBreakdown(): JsonResponse
    {
        $breakdown = $this->repository->getHoldingsBreakdown();

        return response()->json(['data' => $breakdown]);
    }

    public function assetAllocation(): JsonResponse
    {
        $allocation = $this->repository->getAssetAllocation();

        return response()->json(['data' => $allocation]);
    }

    public function profitLoss(): JsonResponse
    {
        $start       = $this->parameters->get('start');
        $end         = $this->parameters->get('end');
        $holdings    = $this->repository->getAllHoldings();
        $realized    = $this->repository->getRealizedGains($start, $end);

        $unrealized = [];
        foreach ($holdings as $holding) {
            $returnPct    = $this->calculator->calculateReturnPercentage(
                bcmul($holding->quantity, $holding->average_cost, 12),
                $holding->current_value ?? '0'
            );
            $unrealized[] = [
                'symbol'          => $holding->symbol,
                'name'            => $holding->name,
                'quantity'        => $holding->quantity,
                'average_cost'    => $holding->average_cost,
                'current_price'   => $holding->current_price,
                'current_value'   => $holding->current_value,
                'unrealized_pnl'  => $holding->unrealized_pnl,
                'return_pct'      => $returnPct,
                'currency_code'   => $holding->price_currency_code,
            ];
        }

        return response()->json([
            'data' => [
                'unrealized' => $unrealized,
                'realized'   => $realized,
            ],
        ]);
    }
}
