<?php

namespace App\Domains\Budgeting\Services;

use App\Commons\MoneyService;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use Carbon\CarbonImmutable;

final class ReservedCostCalculatorService
{
    public function calculateForCurrentCycle(
        int $userId,
        string $cycleKey,
        CarbonImmutable $today,
    ): string {
        $sum = '0.00';

        $occurrences = FixedCostOccurrence::query()
            ->where('user_id', $userId)
            ->where('cycle_key', $cycleKey)
            ->whereIn('status', [
                FixedCostOccurenceStatus::PENDING->value,
                FixedCostOccurenceStatus::OVERDUE->value,
            ])
            ->whereDate('due_date', '>=', $today->toDateString())
            ->get(['amount']);

        foreach ($occurrences as $occurrence) {
            $sum = MoneyService::add($sum, (string) $occurrence->amount);
        }

        return $sum;
    }
}
