<?php

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Portfolio;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Models\PortfolioHolding;
use FireflyIII\Models\PortfolioSnapshot;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

class ChartController extends Controller
{
    /**
     * Returns allocation data for pie/doughnut chart by platform.
     */
    public function allocationByPlatform(): JsonResponse
    {
        /** @var User $user */
        $user     = auth()->user();
        $accounts = PortfolioAccount::where('user_id', $user->id)->where('active', true)->get();

        $labels = [];
        $data   = [];
        $colors = [
            'moomoo' => '#FF6B35',
            'fsmone' => '#1B6B93',
            'luno'   => '#0066FF',
        ];
        $bgColors = [];

        foreach ($accounts as $account) {
            $total = PortfolioHolding::where('portfolio_account_id', $account->id)
                ->sum('market_value');

            if ($total > 0) {
                $labels[]   = ucfirst($account->platform) . ' — ' . $account->name;
                $data[]     = round((float) $total, 2);
                $bgColors[] = $colors[$account->platform] ?? '#999';
            }
        }

        return response()->json([
            'labels'   => $labels,
            'datasets' => [
                [
                    'data'            => $data,
                    'backgroundColor' => $bgColors,
                ],
            ],
        ]);
    }

    /**
     * Returns allocation data by asset type (stock, fund, crypto, etc.).
     */
    public function allocationByAssetType(): JsonResponse
    {
        /** @var User $user */
        $user     = auth()->user();
        $accounts = PortfolioAccount::where('user_id', $user->id)->where('active', true)->pluck('id');

        $holdings = PortfolioHolding::whereIn('portfolio_account_id', $accounts)
            ->selectRaw('asset_type, SUM(market_value) as total_value')
            ->groupBy('asset_type')
            ->get();

        $typeColors = [
            'stock'  => '#4CAF50',
            'etf'    => '#2196F3',
            'fund'   => '#9C27B0',
            'crypto' => '#FF9800',
            'bond'   => '#607D8B',
            'reit'   => '#795548',
            'cash'   => '#9E9E9E',
        ];

        $labels   = [];
        $data     = [];
        $bgColors = [];

        foreach ($holdings as $row) {
            $labels[]   = ucfirst($row->asset_type);
            $data[]     = round((float) $row->total_value, 2);
            $bgColors[] = $typeColors[$row->asset_type] ?? '#999';
        }

        return response()->json([
            'labels'   => $labels,
            'datasets' => [
                [
                    'data'            => $data,
                    'backgroundColor' => $bgColors,
                ],
            ],
        ]);
    }

    /**
     * Returns historical portfolio value for line chart.
     */
    public function historicalValue(): JsonResponse
    {
        /** @var User $user */
        $user     = auth()->user();
        $accounts = PortfolioAccount::where('user_id', $user->id)->where('active', true)->get();

        $datasets = [];

        $colors = [
            'moomoo' => '#FF6B35',
            'fsmone' => '#1B6B93',
            'luno'   => '#0066FF',
        ];

        // Get snapshots for last 90 days
        $startDate = Carbon::now()->subDays(90);

        foreach ($accounts as $account) {
            $snapshots = PortfolioSnapshot::where('portfolio_account_id', $account->id)
                ->where('snapshot_date', '>=', $startDate)
                ->orderBy('snapshot_date')
                ->get();

            if ($snapshots->isEmpty()) {
                continue;
            }

            $datasets[] = [
                'label'       => ucfirst($account->platform) . ' — ' . $account->name,
                'data'        => $snapshots->map(fn ($s) => [
                    'x' => $s->snapshot_date->format('Y-m-d'),
                    'y' => round((float) $s->total_value, 2),
                ])->values()->toArray(),
                'borderColor' => $colors[$account->platform] ?? '#999',
                'fill'        => false,
                'tension'     => 0.3,
            ];
        }

        // If no snapshots exist, create a single-point dataset from current holdings
        if (empty($datasets)) {
            foreach ($accounts as $account) {
                $total = PortfolioHolding::where('portfolio_account_id', $account->id)
                    ->sum('market_value');

                if ($total > 0) {
                    $datasets[] = [
                        'label'       => ucfirst($account->platform) . ' — ' . $account->name,
                        'data'        => [
                            ['x' => Carbon::now()->format('Y-m-d'), 'y' => round((float) $total, 2)],
                        ],
                        'borderColor' => $colors[$account->platform] ?? '#999',
                        'fill'        => false,
                    ];
                }
            }
        }

        return response()->json(['datasets' => $datasets]);
    }

    /**
     * Returns top holdings for horizontal bar chart.
     */
    public function topHoldings(): JsonResponse
    {
        /** @var User $user */
        $user     = auth()->user();
        $accounts = PortfolioAccount::where('user_id', $user->id)->where('active', true)->pluck('id');

        $holdings = PortfolioHolding::whereIn('portfolio_account_id', $accounts)
            ->orderBy('market_value', 'desc')
            ->limit(10)
            ->get();

        $labels = [];
        $values = [];
        $pnls   = [];
        $bgColors = [];

        foreach ($holdings as $holding) {
            $labels[] = $holding->symbol . ' — ' . substr($holding->name, 0, 20);
            $values[] = round((float) $holding->market_value, 2);
            $pnl      = (float) $holding->unrealized_pnl;
            $pnls[]   = round($pnl, 2);
            $bgColors[] = $pnl >= 0 ? '#4CAF50' : '#F44336';
        }

        return response()->json([
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Market Value',
                    'data'            => $values,
                    'backgroundColor' => '#2196F3',
                ],
                [
                    'label'           => 'Unrealized P&L',
                    'data'            => $pnls,
                    'backgroundColor' => $bgColors,
                ],
            ],
        ]);
    }
}
