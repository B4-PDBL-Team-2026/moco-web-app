<?php

namespace App\Domains\Budgeting\Services;

use App\Commons\MoneyService;
use App\Domains\Budgeting\Enums\CycleType;
use Carbon\CarbonImmutable;

class AllowanceCalculatorService
{
    public function calculateInitial(
        string $balance,
        CycleType $cycleType,
        ?string $dailyCeilingAmount,
        ?CarbonImmutable $now = null,
    ): string {
        $now ??= CarbonImmutable::now();

        $daysRemaining = match ($cycleType) {
            CycleType::WEEKLY => $this->remainingDaysInWeek($now),
            CycleType::MONTHLY => $this->remainingDaysInMonth($now),
        };

        $raw = MoneyService::div($balance, (string) $daysRemaining);

        if ($dailyCeilingAmount !== null) {
            return MoneyService::min($raw, $dailyCeilingAmount);
        }

        return $raw;
    }

    private function remainingDaysInWeek(CarbonImmutable $date): int
    {
        // using ISO week format: Monday = 1, Sunday = 7
        return 7 - $date->dayOfWeekIso + 1;
    }

    private function remainingDaysInMonth(CarbonImmutable $date): int
    {
        return $date->daysInMonth - $date->day + 1;
    }
}
