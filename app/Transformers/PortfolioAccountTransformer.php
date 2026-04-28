<?php

declare(strict_types=1);

namespace FireflyIII\Transformers;

use FireflyIII\Models\PortfolioAccount;
use Symfony\Component\HttpFoundation\ParameterBag;

class PortfolioAccountTransformer extends AbstractTransformer
{
    public function __construct()
    {
        $this->parameters = new ParameterBag();
    }

    public function transform(PortfolioAccount $account): array
    {
        return [
            'id'             => (string) $account->id,
            'type'           => 'portfolio_accounts',
            'attributes'     => [
                'name'           => $account->name,
                'platform'       => $account->platform->value,
                'active'         => $account->active,
                'last_synced_at' => $account->last_synced_at?->toAtomString(),
                'created_at'     => $account->created_at->toAtomString(),
                'updated_at'     => $account->updated_at->toAtomString(),
            ],
            'links'          => [
                'self' => route('api.v1.portfolio.accounts.show', [$account->id]),
            ],
        ];
    }
}
