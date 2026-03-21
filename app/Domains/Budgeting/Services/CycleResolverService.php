<?php

namespace App\Domains\Budgeting\Services;

use App\Domains\Budgeting\DTOs\ResolvedCycleData;
use App\Domains\Budgeting\Enums\CycleType;
use Carbon\CarbonImmutable;
use Carbon\Constants\UnitValue;

final class CycleResolverService
{
    /**
     * Calculate the current day's position within the active financial cycle.
     *
     * This method determines how many days have passed since the cycle started,
     * which is essential for calculating daily budget limits and progress.
     */
    public function resolve(CycleType $cycleType, ?CarbonImmutable $now = null, string $timezone = 'Asia/Jakarta'): ResolvedCycleData
    {
        $now ??= CarbonImmutable::now($timezone);
        $now = $now->startOfDay();

        return match ($cycleType) {
            CycleType::MONTHLY => $this->resolveMonthly($now),
            CycleType::WEEKLY => $this->resolveWeekly($now),
        };
    }

    private function resolveMonthly(CarbonImmutable $now): ResolvedCycleData
    {
        $start = $now->startOfMonth();
        $end = $now->endOfMonth();

        return new ResolvedCycleData(
            cycleKey: $now->format('Y-m'),
            startDate: $start,
            endDate: $end,
            remainingDays: $now->diffInDays($end) + 1,
        );
    }

    private function resolveWeekly(CarbonImmutable $now): ResolvedCycleData
    {
        $start = $now->startOfWeek(UnitValue::MONDAY);
        $end = $now->endOfWeek(UnitValue::SUNDAY);

        return new ResolvedCycleData(
            cycleKey: $now->format('o-\WW'),
            startDate: $start,
            endDate: $end,
            remainingDays: $now->diffInDays($end) + 1,
        );
    }
}
