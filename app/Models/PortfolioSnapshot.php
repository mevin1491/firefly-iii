<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioSnapshot extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'portfolio_account_id',
        'snapshot_date',
        'total_value',
        'total_cost',
        'total_pnl',
        'day_change',
        'day_change_pct',
    ];

    protected $casts = [
        'snapshot_date'   => 'date',
        'total_value'     => 'decimal:2',
        'total_cost'      => 'decimal:2',
        'total_pnl'       => 'decimal:2',
        'day_change'      => 'decimal:2',
        'day_change_pct'  => 'decimal:4',
    ];

    public function portfolioAccount(): BelongsTo
    {
        return $this->belongsTo(PortfolioAccount::class);
    }
}
