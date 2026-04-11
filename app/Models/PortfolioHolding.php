<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioHolding extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'portfolio_account_id',
        'symbol',
        'name',
        'asset_type',
        'quantity',
        'avg_cost',
        'current_price',
        'market_value',
        'cost_basis',
        'unrealized_pnl',
        'unrealized_pnl_pct',
        'currency',
    ];

    protected $casts = [
        'quantity'          => 'decimal:8',
        'avg_cost'          => 'decimal:8',
        'current_price'     => 'decimal:8',
        'market_value'      => 'decimal:2',
        'cost_basis'        => 'decimal:2',
        'unrealized_pnl'    => 'decimal:2',
        'unrealized_pnl_pct' => 'decimal:4',
    ];

    public function portfolioAccount(): BelongsTo
    {
        return $this->belongsTo(PortfolioAccount::class);
    }
}
