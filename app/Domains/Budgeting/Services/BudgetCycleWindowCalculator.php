<?php

namespace App\Domains\Budgeting\Services;

use App\Domains\Budgeting\DTOs\ResolvedCycleData;
use App\Domains\Budgeting\Enums\CycleType;
use Carbon\CarbonImmutable;
use Carbon\Constants\UnitValue;

/**
 * Service to calculate the calendar boundaries and metadata for a financial cycle.
 * This class is responsible for determining the exact start date, end date,
 * unique cycle identifier (key), and remaining days for any given point in time,
 * based on the user's configured budget frequency (Monthly or Weekly).
 */
final class BudgetCycleWindowCalculator
{
    /**
     * Calculate the budget cycle window details for a specific reference date.
     *
     * @param  CycleType  $cycleType  The frequency of the budget (e.g., MONTHLY, WEEKLY).
     * @param  CarbonImmutable|null  $now  The reference date to calculate the window around. Defaults to current time if null.
     * @param  string  $timezone  The timezone used to standardize the start/end of the day (Default: 'Asia/Jakarta').
     * @return ResolvedCycleData DTO containing start date, end date, remaining days, and a unique cycle key.
     */
    public function calculateFor(CycleType $cycleType, ?CarbonImmutable $now = null, string $timezone = 'Asia/Jakarta'): ResolvedCycleData
    {
        $now ??= CarbonImmutable::now($timezone);
        $now = $now->startOfDay();

        return match ($cycleType) {
            CycleType::MONTHLY => $this->calculateMonthlyWindow($now),
            CycleType::WEEKLY => $this->calculateWeeklyWindow($now),
        };
    }

    /**
     * Calculate boundaries for a monthly cycle (1st day to the last day of the month).
     *
     * @param  CarbonImmutable  $now  The normalized reference date at the start of the day.
     */
    private function calculateMonthlyWindow(CarbonImmutable $now): ResolvedCycleData
    {
        $start = $now->startOfMonth();
        $end = $now->endOfMonth();

        return new ResolvedCycleData(
            cycleKey: $now->format('Y-m'),
            startDate: $start,
            endDate: $end,
            remainingDays: $this->countRemainingDays($now, $end),
        );
    }

    /**
     * Calculate boundaries for a weekly cycle (Monday to Sunday).
     *
     * @param  CarbonImmutable  $now  The normalized reference date at the start of the day.
     */
    private function calculateWeeklyWindow(CarbonImmutable $now): ResolvedCycleData
    {
        $start = $now->startOfWeek(UnitValue::MONDAY);
        $end = $now->endOfWeek(UnitValue::SUNDAY);

        return new ResolvedCycleData(
            cycleKey: $now->format('o-\WW'),
            startDate: $start,
            endDate: $end,
            remainingDays: $this->countRemainingDays($now, $end),
        );
    }

    /**
     * Calculate remaining days in current month.
     *
     * @param  CarbonImmutable  $now  The normalized reference date at the start of the day.
     * @param  CarbonImmutable  $end  The normalized end date at the start of the day.
     */
    private function countRemainingDays(CarbonImmutable $now, CarbonImmutable $end): int
    {
        return (int) round($now->diffInDays($end));
    }
}
