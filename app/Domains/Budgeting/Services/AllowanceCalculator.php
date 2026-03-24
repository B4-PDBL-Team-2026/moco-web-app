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

        $available = MoneyService::sub($balance, $reservedCost);

        $rawAmount = MoneyService::div($available, (string) $remainingDays);

        $cappedAmount = MoneyService::min($rawAmount, $ceilingLimit);

        if (MoneyService::gt($balance, $reservedCost)) {

            if ($rawAmount > $flooringLimit) {
                return new DailyAllowanceData(
                    amount: $cappedAmount,
                    actualAmount: $rawAmount,
                );
            } else {
                return new DailyAllowanceData(
                    amount: $flooringLimit,
                    actualAmount: $rawAmount,
                );
            }

        } elseif (MoneyService::eq($balance, $reservedCost)) {
            return new DailyAllowanceData(
                amount: $flooringLimit,
                actualAmount: '0.00',
            );
        } else {
            return new DailyAllowanceData(
                amount: $flooringLimit,
                actualAmount: '0.00',
            );
        }
    }
}
