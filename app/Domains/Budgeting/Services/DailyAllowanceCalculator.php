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
            throw new InvalidArgumentException('Remaining days must be greater than 0.');        }

        // balance - reserved
        $available = MoneyService::sub($balance, $reservedCost);

                $rawAmount = MoneyService::div($available, (string) $remainingDays);

        $cappedAmount = MoneyService::min($rawAmount, $ceilingLimit);

        if (MoneyService::gt($balance, $reservedCost)) {

            // stable or surplus
            if (MoneyService::gte($rawAmount, $flooringLimit)) {
                return new DailyAllowanceData(
                    amount: $cappedAmount,
                    rawAmount: $rawAmount,
                );
            }

            // critical but still can cover fixed costs
            return new DailyAllowanceData(
                amount: $flooringLimit,
                rawAmount: $rawAmount,
            );

        } elseif (MoneyService::eq($balance, $reservedCost)) {

            // zero after fixed cost
            return new DailyAllowanceData(
                amount: $flooringLimit,
                rawAmount: '0.00',
            );

        }

        // deficit condition
        return new DailyAllowanceData(
            amount: $flooringLimit,
            rawAmount: '0.00',
        );

        // raw = available / days
        $raw = MoneyService::div($available, (string) $remainingDays);

        // clamp
        if (MoneyService::lt($raw, $flooringLimit)) {
            return new DailyAllowanceData(
                amount: $flooringLimit,
                rawAmount: $raw
            );
        }

        if (MoneyService::gt($raw, $ceilingLimit)) {
            return new DailyAllowanceData(
                amount: $ceilingLimit,
                rawAmount: $raw
            );
        }

        return new DailyAllowanceData(
            amount: $raw,
            rawAmount: $raw
        );
    }
}