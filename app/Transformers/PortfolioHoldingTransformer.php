<?php

declare(strict_types=1);

namespace FireflyIII\Transformers;

use FireflyIII\Models\PortfolioHolding;
use Symfony\Component\HttpFoundation\ParameterBag;

class PortfolioHoldingTransformer extends AbstractTransformer
{
    public function __construct()
    {
        $this->parameters = new ParameterBag();
    }

    public function transform(PortfolioHolding $holding): array
    {
        return [
            'id'         => (string) $holding->id,
            'type'       => 'portfolio_holdings',
            'attributes' => [
                'portfolio_account_id' => (string) $holding->portfolio_account_id,
                'symbol'               => $holding->symbol,
                'name'                 => $holding->name,
                'asset_class'          => $holding->asset_class->value,
                'quantity'             => $holding->quantity,
                'average_cost'         => $holding->average_cost,
                'cost_currency_code'   => $holding->cost_currency_code,
                'current_price'        => $holding->current_price,
                'price_currency_code'  => $holding->price_currency_code,
                'current_value'        => $holding->current_value,
                'unrealized_pnl'       => $holding->unrealized_pnl,
                'last_price_update'    => $holding->last_price_update?->toAtomString(),
            ],
        ];
    }
}
