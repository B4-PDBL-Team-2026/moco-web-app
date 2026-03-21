<?php

namespace App\Domains\Budgeting\Services;

use App\Commons\MoneyService;
use App\Domains\Budgeting\DTOs\DailyAllowanceData;
use InvalidArgumentException;

class AllowanceCalculatorService
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

        // fallback to flooring limit
        if (MoneyService::gte($reservedCost, $balance)) {
            return new DailyAllowanceData(
                amount: $flooringLimit,
                actualAmount: '0',
            );
        }

        $available = MoneyService::sub($balance, $reservedCost);
        $raw = MoneyService::div($available, (string) $remainingDays);

        // if available daily allowance is less than flooring limit, prefer flooring limit for daily survival
        if (MoneyService::lt($raw, $flooringLimit)) {
            return new DailyAllowanceData(
                amount: MoneyService::max($raw, $flooringLimit),
                actualAmount: $raw,
            );
        } else {
            // otherwise, avoid excessive daily allowance
            return new DailyAllowanceData(
                amount: MoneyService::min($raw, $ceilingLimit),
                actualAmount: $raw,
            );
        }
    }
}
