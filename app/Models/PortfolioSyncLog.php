<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioSyncLog extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = [
        'portfolio_account_id',
        'status',
        'message',
        'records_synced',
    ];

    protected $casts = [
        'records_synced' => 'integer',
    ];

    public function portfolioAccount(): BelongsTo
    {
        return $this->belongsTo(PortfolioAccount::class);
    }
}
