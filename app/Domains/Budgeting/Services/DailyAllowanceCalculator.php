<?php

namespace App\Domains\Budgeting\Services;

use App\Commons\Services\MoneyService;
use App\Domains\Budgeting\DTOs\DailyAllowanceData;
use InvalidArgumentException;

class DailyAllowanceCalculator
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
            // stable or surplus always return flooring <= amount <= ceiling
            if ($rawAmount >= $flooringLimit) {
                return new DailyAllowanceData(
                    amount: $cappedAmount,
                    rawAmount: $rawAmount,
                );
            } else {
                // critical but still able to pay bills from fixed cost
                return new DailyAllowanceData(
                    amount: $flooringLimit,
                    rawAmount: $rawAmount,
                );
            }

        } elseif (MoneyService::eq($balance, $reservedCost)) {
            // critical with 0 balance after fixed costs payment
            return new DailyAllowanceData(
                amount: $flooringLimit,
                rawAmount: '0.00',
            );
        } else {
            // defisit balance, prioritize daily allowance but can not pay fixed cost bills
            return new DailyAllowanceData(
                amount: $flooringLimit,
                rawAmount: '0.00',
            );
        }
    }
}
