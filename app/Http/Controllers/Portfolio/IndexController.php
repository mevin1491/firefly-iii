<?php

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Portfolio;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Models\PortfolioHolding;
use FireflyIII\Models\PortfolioSnapshot;
use FireflyIII\Models\PortfolioTransaction;
use FireflyIII\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        app('view')->share('mainTitleIcon', 'fa-line-chart');
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', 'Portfolio Tracker');

                return $next($request);
            }
        );
    }

    /**
     * Main dashboard showing unified portfolio view across all platforms.
     */
    public function index(): View
    {
        /** @var User $user */
        $user     = auth()->user();
        $accounts = PortfolioAccount::where('user_id', $user->id)
            ->where('active', true)
            ->get();

        // Aggregate holdings across all accounts
        $allHoldings = PortfolioHolding::whereIn('portfolio_account_id', $accounts->pluck('id'))
            ->orderBy('market_value', 'desc')
            ->get();

        // Summary stats
        $totalValue       = $allHoldings->sum('market_value');
        $totalCost        = $allHoldings->sum('cost_basis');
        $totalPnl         = $totalValue - $totalCost;
        $totalPnlPct      = $totalCost > 0 ? ($totalPnl / $totalCost) * 100 : 0;

        // Group by platform
        $holdingsByPlatform = [];
        foreach ($accounts as $account) {
            $holdings = $allHoldings->where('portfolio_account_id', $account->id);
            if ($holdings->isEmpty()) {
                continue;
            }
            $holdingsByPlatform[$account->id] = [
                'account'  => $account,
                'holdings' => $holdings,
                'total'    => $holdings->sum('market_value'),
                'cost'     => $holdings->sum('cost_basis'),
                'pnl'      => $holdings->sum('market_value') - $holdings->sum('cost_basis'),
            ];
        }

        // Group by asset type
        $holdingsByType = $allHoldings->groupBy('asset_type')->map(function ($group) {
            return [
                'count'    => $group->count(),
                'value'    => $group->sum('market_value'),
                'cost'     => $group->sum('cost_basis'),
                'pnl'      => $group->sum('market_value') - $group->sum('cost_basis'),
            ];
        });

        // Recent transactions (last 20)
        $recentTransactions = PortfolioTransaction::whereIn('portfolio_account_id', $accounts->pluck('id'))
            ->orderBy('transacted_at', 'desc')
            ->limit(20)
            ->get();

        $pageTitle = 'Portfolio Dashboard';
        $subTitle  = 'Unified view across Moomoo, FSMOne & Luno';

        return view('portfolio.index', compact(
            'accounts',
            'allHoldings',
            'totalValue',
            'totalCost',
            'totalPnl',
            'totalPnlPct',
            'holdingsByPlatform',
            'holdingsByType',
            'recentTransactions',
            'pageTitle',
            'subTitle'
        ));
    }
}
