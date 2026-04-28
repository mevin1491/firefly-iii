<?php

declare(strict_types=1);

namespace FireflyIII\Transformers;

use FireflyIII\Models\PortfolioTransaction;
use Symfony\Component\HttpFoundation\ParameterBag;

class PortfolioTransactionTransformer extends AbstractTransformer
{
    public function __construct()
    {
        $this->parameters = new ParameterBag();
    }

    public function transform(PortfolioTransaction $transaction): array
    {
        return [
            'id'         => (string) $transaction->id,
            'type'       => 'portfolio_transactions',
            'attributes' => [
                'portfolio_account_id' => (string) $transaction->portfolio_account_id,
                'symbol'               => $transaction->symbol,
                'transaction_type'     => $transaction->transaction_type->value,
                'quantity'             => $transaction->quantity,
                'price_per_unit'       => $transaction->price_per_unit,
                'total_amount'         => $transaction->total_amount,
                'currency_code'        => $transaction->currency_code,
                'fees'                 => $transaction->fees,
                'transacted_at'        => $transaction->transacted_at->toAtomString(),
                'external_id'          => $transaction->external_id,
                'notes'                => $transaction->notes,
            ],
        ];
    }
}
