<?php

declare(strict_types=1);

namespace FireflyIII\Enums;

enum PortfolioAssetClassEnum: string
{
    case STOCK  = 'stock';
    case ETF    = 'etf';
    case FUND   = 'fund';
    case CRYPTO = 'crypto';
    case BOND   = 'bond';
    case CASH   = 'cash';
}
