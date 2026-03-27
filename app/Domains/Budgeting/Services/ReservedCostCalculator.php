<?php

namespace App\Domains\Budgeting\Services;

use App\Commons\Services\MoneyService;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use Carbon\CarbonImmutable;

/**
 * Calculates the total reserved funds required for a specific budget window.
 */
final class ReservedCostCalculator
{
    /**
     * Calculate total reserved cost for the given budget window.
     */
    public function calculateForBudgetWindow(
        int $userId,
        CarbonImmutable $budgetWindowStart,
        CarbonImmutable $budgetWindowEnd,
    ): string {

        $sum = '0.00';

        $occurrences = FixedCostOccurrence::query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                FixedCostOccurenceStatus::PENDING,
                FixedCostOccurenceStatus::OVERDUE,
            ])
            ->whereDate('due_date', '>=', $budgetWindowStart->toDateString())
            ->whereDate('due_date', '<=', $budgetWindowEnd->toDateString())
            ->get(['amount']);

        foreach ($occurrences as $occurrence) {
            $sum = MoneyService::add($sum, (string) $occurrence->amount);
        }

        return $sum;
    }
}