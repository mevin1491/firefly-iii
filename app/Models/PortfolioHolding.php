<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Enums\PortfolioAssetClassEnum;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioHolding extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'portfolio_account_id', 'symbol', 'name', 'asset_class',
        'quantity', 'average_cost', 'cost_currency_code',
        'current_price', 'price_currency_code',
        'current_value', 'unrealized_pnl', 'last_price_update',
    ];

    protected function casts(): array
    {
        return [
            'asset_class'       => PortfolioAssetClassEnum::class,
            'quantity'          => 'string',
            'average_cost'      => 'string',
            'current_price'     => 'string',
            'current_value'     => 'string',
            'unrealized_pnl'    => 'string',
            'last_price_update' => 'datetime',
        ];
    }

    public function portfolioAccount(): BelongsTo
    {
        return $this->belongsTo(PortfolioAccount::class);
    }
}
