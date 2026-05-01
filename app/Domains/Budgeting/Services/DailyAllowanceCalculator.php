<?php

namespace App\Domains\Budgeting\Services;

use App\Commons\ValueObjects\Money;
use App\Domains\Budgeting\DTOs\DailyAllowanceData;
use InvalidArgumentException;

class DailyAllowanceCalculator
{
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

        $available = Money::sub($balance, $reservedCost);
        $rawAmount = Money::div($available, (string) $remainingDays);
        $cappedAmount = Money::min($rawAmount, $ceilingLimit);

        if (Money::gt($balance, $reservedCost)) {
            if (Money::gte($rawAmount, $flooringLimit)) {
                return new DailyAllowanceData(
                    amount: $cappedAmount,
                    rawAmount: $rawAmount,
                );
            } else {
                return new DailyAllowanceData(
                    amount: $flooringLimit,
                    rawAmount: $rawAmount,
                );
            }
        } elseif (Money::eq($balance, $reservedCost)) {
            return new DailyAllowanceData(
                amount: $flooringLimit,
                rawAmount: '0.00',
            );
        } else {
            return new DailyAllowanceData(
                amount: $flooringLimit,
                rawAmount: '0.00',
            );
        }
    }
}
