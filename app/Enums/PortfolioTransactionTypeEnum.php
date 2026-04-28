<?php

declare(strict_types=1);

namespace FireflyIII\Enums;

enum PortfolioTransactionTypeEnum: string
{
    case BUY        = 'buy';
    case SELL       = 'sell';
    case DIVIDEND   = 'dividend';
    case FEE        = 'fee';
    case DEPOSIT    = 'deposit';
    case WITHDRAWAL = 'withdrawal';
}
