<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Portfolio;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Repositories\Portfolio\PortfolioRepositoryInterface;
use FireflyIII\Transformers\PortfolioTransactionTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionController extends Controller
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

    public function index(Request $request): JsonResponse
    {
        $account = null;
        $start   = $this->parameters->get('start');
        $end     = $this->parameters->get('end');

        $accountId = $request->query('account_id');
        if (null !== $accountId) {
            $account = $this->repository->getAccountById((int) $accountId);
        }

        $transactions = $this->repository->getTransactions($account, $start, $end);
        $transformer  = new PortfolioTransactionTransformer();
        $paginator    = new LengthAwarePaginator($transactions, $transactions->count(), max($transactions->count(), 1));
        $data         = $this->jsonApiList('portfolio_transactions', $paginator, $transformer);

        return response()->json($data);
    }
}
