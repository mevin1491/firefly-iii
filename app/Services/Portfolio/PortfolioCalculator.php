<?php

declare(strict_types=1);

namespace FireflyIII\Services\Portfolio;

use FireflyIII\Models\PortfolioHolding;
use Illuminate\Support\Collection;

class PortfolioCalculator
{
    public function calculateUnrealizedPnl(PortfolioHolding $holding): string
    {
        if (null === $holding->current_price) {
            return '0';
        }
        $currentValue = bcmul($holding->quantity, $holding->current_price, 12);
        $costBasis    = bcmul($holding->quantity, $holding->average_cost, 12);

        return bcsub($currentValue, $costBasis, 12);
    }

    public function calculateCurrentValue(PortfolioHolding $holding): string
    {
        if (null === $holding->current_price) {
            return '0';
        }

        return bcmul($holding->quantity, $holding->current_price, 12);
    }

    public function calculateReturnPercentage(string $cost, string $currentValue): string
    {
        if (0 === bccomp($cost, '0', 12)) {
            return '0';
        }
        $gain = bcsub($currentValue, $cost, 12);

        return bcmul(bcdiv($gain, $cost, 12), '100', 4);
    }

    public function calculateAllocation(Collection $holdings): array
    {
        $total      = '0';
        $allocation = [];

        foreach ($holdings as $holding) {
            $value = $holding->current_value ?? '0';
            $total = bcadd($total, $value, 12);
        }

        if (0 === bccomp($total, '0', 12)) {
            return [];
        }

        foreach ($holdings as $holding) {
            $value                         = $holding->current_value ?? '0';
            $pct                           = bcmul(bcdiv($value, $total, 12), '100', 4);
            $allocation[$holding->symbol]  = [
                'symbol'     => $holding->symbol,
                'name'       => $holding->name,
                'value'      => $value,
                'percentage' => $pct,
            ];
        }

        return $allocation;
    }

    public function calculateRealizedGains(Collection $transactions): array
    {
        $gains = [];
        foreach ($transactions as $txn) {
            if ('sell' === $txn->transaction_type->value) {
                $symbol = $txn->symbol;
                if (!isset($gains[$symbol])) {
                    $gains[$symbol] = [
                        'symbol'        => $symbol,
                        'total_proceeds' => '0',
                        'total_fees'    => '0',
                        'currency_code' => $txn->currency_code,
                    ];
                }
                $gains[$symbol]['total_proceeds'] = bcadd($gains[$symbol]['total_proceeds'], $txn->total_amount, 12);
                $gains[$symbol]['total_fees']     = bcadd($gains[$symbol]['total_fees'], $txn->fees, 12);
            }
        }

        return array_values($gains);
    }
}
