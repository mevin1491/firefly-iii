<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioImportLog extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;

    protected $fillable = [
        'user_id', 'portfolio_account_id', 'filename',
        'rows_imported', 'rows_skipped', 'status', 'errors',
    ];

    protected function casts(): array
    {
        return [
            'rows_imported' => 'integer',
            'rows_skipped'  => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function portfolioAccount(): BelongsTo
    {
        return $this->belongsTo(PortfolioAccount::class);
    }
}
