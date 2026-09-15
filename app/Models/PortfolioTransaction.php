<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioTransaction extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'portfolio_account_id',
        'symbol',
        'name',
        'type',
        'quantity',
        'price',
        'amount',
        'fee',
        'currency',
        'external_id',
        'transacted_at',
    ];

    protected $casts = [
        'quantity'      => 'decimal:8',
        'price'         => 'decimal:8',
        'amount'        => 'decimal:2',
        'fee'           => 'decimal:2',
        'transacted_at' => 'datetime',
    ];

    public function portfolioAccount(): BelongsTo
    {
        return $this->belongsTo(PortfolioAccount::class);
    }
}
