<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Enums\PortfolioPlatformEnum;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PortfolioAccount extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $fillable = ['user_id', 'user_group_id', 'name', 'platform', 'active', 'last_synced_at'];

    protected function casts(): array
    {
        return [
            'active'         => 'boolean',
            'platform'       => PortfolioPlatformEnum::class,
            'last_synced_at' => 'datetime',
        ];
    }

    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            /** @var User $user */
            $user    = auth()->user();
            $account = $user->portfolioAccounts()->find((int) $value);
            if (null !== $account) {
                return $account;
            }
        }
        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(PortfolioHolding::class);
    }

    public function portfolioTransactions(): HasMany
    {
        return $this->hasMany(PortfolioTransaction::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PortfolioSnapshot::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(PortfolioImportLog::class);
    }
}
