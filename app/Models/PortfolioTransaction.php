<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Enums\PortfolioTransactionTypeEnum;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioTransaction extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'portfolio_account_id', 'symbol', 'transaction_type',
        'quantity', 'price_per_unit', 'total_amount',
        'currency_code', 'fees', 'transacted_at',
        'external_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => PortfolioTransactionTypeEnum::class,
            'quantity'         => 'string',
            'price_per_unit'   => 'string',
            'total_amount'     => 'string',
            'fees'             => 'string',
            'transacted_at'    => 'datetime',
        ];
    }

    public function portfolioAccount(): BelongsTo
    {
        return $this->belongsTo(PortfolioAccount::class);
    }
}
