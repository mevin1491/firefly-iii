<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Portfolio;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\PortfolioHolding;
use FireflyIII\Repositories\Portfolio\PortfolioRepositoryInterface;
use FireflyIII\Transformers\PortfolioHoldingTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class HoldingController extends Controller
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
        $accountId = $request->query('account_id');
        if (null !== $accountId) {
            $account  = $this->repository->getAccountById((int) $accountId);
            $holdings = null !== $account ? $this->repository->getHoldingsByAccount($account) : collect();
        } else {
            $holdings = $this->repository->getAllHoldings();
        }

        $transformer = new PortfolioHoldingTransformer();
        $paginator   = new LengthAwarePaginator($holdings, $holdings->count(), max($holdings->count(), 1));
        $data        = $this->jsonApiList('portfolio_holdings', $paginator, $transformer);

        return response()->json($data);
    }

    public function show(PortfolioHolding $portfolioHolding): JsonResponse
    {
        $transformer = new PortfolioHoldingTransformer();
        $data        = $this->jsonApiObject('portfolio_holdings', $portfolioHolding, $transformer);

        return response()->json($data);
    }
}
