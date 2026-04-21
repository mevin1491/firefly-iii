<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Portfolio;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Portfolio\ImportRequest;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Repositories\Portfolio\PortfolioRepositoryInterface;
use FireflyIII\Services\Portfolio\FSMOneImporter;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    private PortfolioRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            $this->repository = app(PortfolioRepositoryInterface::class);
            $this->repository->setUser(auth()->user());

            return $next($request);
        });
    }

    public function import(ImportRequest $request, PortfolioAccount $portfolioAccount): JsonResponse
    {
        $file       = $request->file('file');
        $importType = $request->input('import_type', 'holdings');
        $importer   = app(FSMOneImporter::class);

        $importLog = $this->repository->storeImportLog([
            'portfolio_account_id' => $portfolioAccount->id,
            'filename'             => $file->getClientOriginalName(),
            'status'               => 'processing',
        ]);

        $skipped  = 0;
        $imported = 0;

        if ('transactions' === $importType) {
            $transactions = $importer->parseTransactions($file);
            $imported     = $this->repository->storeTransactions($portfolioAccount, $transactions);
            $skipped      = count($transactions) - $imported;
        } else {
            $holdings = $importer->parseHoldings($file);
            foreach ($holdings as $holding) {
                $this->repository->upsertHolding($portfolioAccount, $holding);
                ++$imported;
            }
        }

        $importLog->update([
            'rows_imported' => $imported,
            'rows_skipped'  => $skipped,
            'status'        => 'completed',
        ]);

        return response()->json([
            'message'       => sprintf('Import completed: %d imported, %d skipped.', $imported, $skipped),
            'rows_imported' => $imported,
            'rows_skipped'  => $skipped,
        ]);
    }
}
