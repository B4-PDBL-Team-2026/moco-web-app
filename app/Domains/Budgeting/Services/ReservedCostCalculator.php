<?php

namespace App\Domains\Budgeting\Services;

use App\Commons\Services\MoneyService;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use Carbon\CarbonImmutable;

/**
 * Calculates the total reserved funds required for a specific budget window.
 * This calculator aggregates all unpaid fixed costs (pending or overdue)
 * that fall within the user's active budget cycle. This ensures the user
 * retains enough balance to cover mandatory upcoming bills.
 */
final class ReservedCostCalculator
{
    /**
     * Calculate the total amount of reserved costs for the given window.
     *
     * @param  int  $userId  The ID of the user owning the budget.
     * @param  CarbonImmutable  $budgetWindowStart  The exact start date of the budget cycle.
     * @param  CarbonImmutable  $budgetWindowEnd  The exact end date of the budget cycle.
     * @return string The total reserved amount, formatted as a string to preserve decimal accuracy.
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
