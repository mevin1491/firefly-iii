<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;

class PortfolioPrice extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = ['symbol', 'platform', 'price', 'currency_code', 'priced_at'];

    protected function casts(): array
    {
        return [
            'price'     => 'string',
            'priced_at' => 'date',
        ];
    }
}
