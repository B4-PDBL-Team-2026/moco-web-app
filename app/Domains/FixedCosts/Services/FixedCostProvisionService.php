<?php

namespace App\Domains\FixedCosts\Services;

use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Calculates the total reserved cost for a user within the current budget window.
 *
 * - The reserved cost is the sum of all active fixed cost occurrences whose
 *   due date is in the future (greater than equal today).
 * - Occurrences whose due date has already passed (overdue/past) are excluded
 *   from the reserved cost for the current cycle — they will be included in the
 *   next cycle's reservation once regenerated.
 *
 * Only PENDING and OVERDUE statuses with a future due date contribute to the reserve.
 * PAID and VOID occurrences are excluded entirely.
 */
final readonly class FixedCostProvisionService
{
    /**
     * Compute the total reserved cost for a user's current budget window.
     *
     * @param  int  $userId  The user whose reserved cost is being calculated.
     * @param  string  $cycleKey  The current cycle key (e.g. "2026-03" or "2026-W12").
     * @param  CarbonImmutable  $today  Reference date for "future due date" determination.
     * @return string Precise decimal string (bcmath-safe).
     */
    public function calculateReservedCost(int $userId, string $cycleKey): string
    {
        $registrationDate = User::query()
            ->findOrFail($userId, ['created_at'])
            ->created_at
            ->toImmutable()
            ->startOfDay()
            ->toDateString();

        $totalOccurrences = FixedCostOccurrence::query()
            ->where('user_id', $userId)
            ->where('cycle_key', $cycleKey)
            ->whereIn('status', [
                FixedCostOccurenceStatus::PENDING->value,
                FixedCostOccurenceStatus::OVERDUE->value,
            ])
            ->where('due_date', '>=', $registrationDate)
            ->sum('amount');

        return number_format((float) $totalOccurrences, 2, '.', '');
    }
}
