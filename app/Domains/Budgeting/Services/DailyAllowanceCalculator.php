<?php

namespace App\Domains\Budgeting\Services;

use App\Commons\Services\MoneyService;
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

        $available = MoneyService::sub($balance, $reservedCost);
        $rawAmount = MoneyService::div($available, (string) $remainingDays);
        $cappedAmount = MoneyService::min($rawAmount, $ceilingLimit);

        if (MoneyService::gt($balance, $reservedCost)) {
            if (MoneyService::gte($rawAmount, $flooringLimit)) {
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
        } elseif (MoneyService::eq($balance, $reservedCost)) {
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
