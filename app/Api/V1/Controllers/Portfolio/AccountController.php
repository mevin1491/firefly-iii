<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Portfolio;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Portfolio\StoreAccountRequest;
use FireflyIII\Api\V1\Requests\Portfolio\UpdateAccountRequest;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Repositories\Portfolio\PortfolioRepositoryInterface;
use FireflyIII\Transformers\PortfolioAccountTransformer;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
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

    public function index(): JsonResponse
    {
        $accounts    = $this->repository->getAccounts();
        $transformer = new PortfolioAccountTransformer();
        $data        = $this->jsonApiList('portfolio_accounts', new \Illuminate\Pagination\LengthAwarePaginator($accounts, $accounts->count(), $accounts->count() ?: 1), $transformer);

        return response()->json($data);
    }

    public function show(PortfolioAccount $portfolioAccount): JsonResponse
    {
        $transformer = new PortfolioAccountTransformer();
        $data        = $this->jsonApiObject('portfolio_accounts', $portfolioAccount, $transformer);

        return response()->json($data);
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $this->repository->storeAccount($request->getAll());
        $transformer = new PortfolioAccountTransformer();
        $data    = $this->jsonApiObject('portfolio_accounts', $account, $transformer);

        return response()->json($data, 201);
    }

    public function update(UpdateAccountRequest $request, PortfolioAccount $portfolioAccount): JsonResponse
    {
        $account     = $this->repository->updateAccount($portfolioAccount, $request->getAll());
        $transformer = new PortfolioAccountTransformer();
        $data        = $this->jsonApiObject('portfolio_accounts', $account, $transformer);

        return response()->json($data);
    }

    public function destroy(PortfolioAccount $portfolioAccount): JsonResponse
    {
        $this->repository->destroyAccount($portfolioAccount);

        return response()->json([], 204);
    }
}
