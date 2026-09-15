<?php

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Portfolio;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Models\PortfolioHolding;
use FireflyIII\Models\PortfolioSyncLog;
use FireflyIII\Services\Portfolio\FSMOneService;
use FireflyIII\Services\Portfolio\LunoService;
use FireflyIII\Services\Portfolio\MoomooService;
use FireflyIII\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
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
     * Show form to create/manage portfolio accounts.
     */
    public function settings(): View
    {
        /** @var User $user */
        $user     = auth()->user();
        $accounts = PortfolioAccount::where('user_id', $user->id)->get();
        $syncLogs = PortfolioSyncLog::whereIn('portfolio_account_id', $accounts->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $pageTitle = 'Portfolio Settings';
        $subTitle  = 'Manage platform connections';

        return view('portfolio.settings', compact('accounts', 'syncLogs', 'pageTitle', 'subTitle'));
    }

    /**
     * Store a new portfolio account.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'platform'   => 'required|in:moomoo,fsmone,luno',
            'name'       => 'required|string|max:255',
            'currency'   => 'required|string|max:10',
            'api_key'    => 'nullable|string',
            'api_secret' => 'nullable|string',
        ]);

        /** @var User $user */
        $user = auth()->user();

        $data = [
            'user_id'  => $user->id,
            'platform' => $request->get('platform'),
            'name'     => $request->get('name'),
            'currency' => strtoupper($request->get('currency')),
            'active'   => true,
        ];

        if ($request->filled('api_key')) {
            $data['api_key'] = encrypt($request->get('api_key'));
        }
        if ($request->filled('api_secret')) {
            $data['api_secret'] = encrypt($request->get('api_secret'));
        }

        PortfolioAccount::create($data);

        session()->flash('success', sprintf('%s account "%s" created successfully.', ucfirst($request->get('platform')), $request->get('name')));

        return redirect(route('portfolio.settings'));
    }

    /**
     * Delete a portfolio account.
     */
    public function destroy(PortfolioAccount $portfolioAccount): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ((int) $portfolioAccount->user_id !== (int) $user->id) {
            abort(403);
        }

        $name = $portfolioAccount->name;
        $portfolioAccount->delete();

        session()->flash('success', sprintf('Portfolio account "%s" deleted.', $name));

        return redirect(route('portfolio.settings'));
    }

    /**
     * Sync a Luno account via API.
     */
    public function syncLuno(PortfolioAccount $portfolioAccount): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ((int) $portfolioAccount->user_id !== (int) $user->id || 'luno' !== $portfolioAccount->platform) {
            abort(403);
        }

        $service = new LunoService($portfolioAccount);
        $log     = $service->syncBalances();

        if ('success' === $log->status) {
            $service->syncTransactions();
            session()->flash('success', $log->message);
        } else {
            session()->flash('error', $log->message);
        }

        return redirect(route('portfolio.index'));
    }

    /**
     * Show CSV import form.
     */
    public function importForm(PortfolioAccount $portfolioAccount): View
    {
        /** @var User $user */
        $user = auth()->user();

        if ((int) $portfolioAccount->user_id !== (int) $user->id) {
            abort(403);
        }

        $pageTitle = 'Import Data';
        $subTitle  = sprintf('Import CSV for %s — %s', ucfirst($portfolioAccount->platform), $portfolioAccount->name);

        return view('portfolio.import', compact('portfolioAccount', 'pageTitle', 'subTitle'));
    }

    /**
     * Process CSV import for Moomoo or FSMOne.
     */
    public function importCsv(Request $request, PortfolioAccount $portfolioAccount): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ((int) $portfolioAccount->user_id !== (int) $user->id) {
            abort(403);
        }

        $request->validate([
            'csv_file'    => 'required|file|mimes:csv,txt|max:10240',
            'import_type' => 'required|in:holdings,transactions',
        ]);

        $csvContent = file_get_contents($request->file('csv_file')->getRealPath());
        $importType = $request->get('import_type');

        if ('moomoo' === $portfolioAccount->platform) {
            $service = new MoomooService($portfolioAccount);

            if ('holdings' === $importType) {
                $log = $service->importHoldingsCsv($csvContent);
            } else {
                $log = $service->importTransactionsCsv($csvContent);
            }
        } elseif ('fsmone' === $portfolioAccount->platform) {
            $service = new FSMOneService($portfolioAccount);

            if ('holdings' === $importType) {
                $log = $service->importHoldingsCsv($csvContent);
            } else {
                $log = $service->importTransactionsCsv($csvContent);
            }
        } else {
            session()->flash('error', 'CSV import is only supported for Moomoo and FSMOne accounts.');

            return redirect(route('portfolio.settings'));
        }

        if ('success' === $log->status) {
            session()->flash('success', $log->message);
        } else {
            session()->flash('error', $log->message);
        }

        return redirect(route('portfolio.index'));
    }

    /**
     * Import from Moomoo FutuOpenD bridge JSON.
     */
    public function importBridgeJson(Request $request, PortfolioAccount $portfolioAccount): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ((int) $portfolioAccount->user_id !== (int) $user->id || 'moomoo' !== $portfolioAccount->platform) {
            abort(403);
        }

        $request->validate([
            'json_file' => 'required|file|mimes:json,txt|max:10240',
        ]);

        $jsonContent = file_get_contents($request->file('json_file')->getRealPath());
        $service     = new MoomooService($portfolioAccount);
        $log         = $service->importBridgeJson($jsonContent);

        if ('success' === $log->status) {
            session()->flash('success', $log->message);
        } else {
            session()->flash('error', $log->message);
        }

        return redirect(route('portfolio.index'));
    }
}
