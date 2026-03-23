<?php

namespace App\Domains\Budgeting\Services;

use App\Commons\MoneyService;
use App\Domains\Budgeting\DTOs\DailyAllowanceData;
use InvalidArgumentException;

class AllowanceCalculator
{
    /**
     * Calculate the safe and actual daily allowance.
     *
     * @param  string  $balance  The user's current actual balance.
     * @param  string  $reservedCost  Total amount of pending/overdue fixed costs.
     * @param  string  $ceilingLimit  The maximum allowed daily allowance (if any).
     * @param  string  $flooringLimit  The absolute minimum daily allowance for survival.
     * @param  int  $remainingDays  Days left in the current budget cycle.
     *
     * @throws InvalidArgumentException If remaining days is zero or negative.
     */
    public function calculate(
        string $balance,
        string $reservedCost,
        string $ceilingLimit,
        string $flooringLimit,
        int $remainingDays,
    ): DailyAllowanceData {
        if ($remainingDays <= 0) {
            throw new InvalidArgumentException('Remaining days must be greater than 0.');
        }

        // fallback to flooring limit
        if (MoneyService::gte($reservedCost, $balance)) {
            return new DailyAllowanceData(
                amount: $flooringLimit,
                actualAmount: '0.00',
            );
        }

        $available = MoneyService::sub($balance, $reservedCost);
        $raw = MoneyService::div($available, (string) $remainingDays);

        // if available daily allowance is dangerously low, then use flooring
        if (MoneyService::lt($raw, $flooringLimit)) {
            return new DailyAllowanceData(
                amount: $flooringLimit,
                actualAmount: $raw,
            );
        }

        // calculate safe ceiling limit
        $cappedAmount = MoneyService::min($raw, $ceilingLimit);

        return new DailyAllowanceData(
            amount: $cappedAmount,
            actualAmount: $raw,
        );
    }
}
