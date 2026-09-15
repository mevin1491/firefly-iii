<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioAccount extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;

    protected $fillable = [
        'user_id',
        'platform',
        'name',
        'api_key',
        'api_secret',
        'currency',
        'active',
        'last_synced_at',
    ];

    protected $casts = [
        'active'         => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(PortfolioHolding::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PortfolioTransaction::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PortfolioSnapshot::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(PortfolioSyncLog::class);
    }

    public function getDecryptedApiKeyAttribute(): ?string
    {
        return $this->api_key ? decrypt($this->api_key) : null;
    }

    public function getDecryptedApiSecretAttribute(): ?string
    {
        return $this->api_secret ? decrypt($this->api_secret) : null;
    }
}
