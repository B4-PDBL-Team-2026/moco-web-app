<?php

namespace App\Domains\FixedCost\Actions;

use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Returns all fixed cost occurrences for the user's current budget cycle.
 *
 * Uses the cycle_key stored in the user's budget snapshot as the reference point,
 * so the result is always consistent with what the snapshot considers "current".
 *
 * Results are ordered by due_date ascending so the consumer sees upcoming
 * bills in chronological order.
 */
final readonly class ListCurrentCycleOccurrencesAction
{
    /**
     * @param  int  $userId  The authenticated user's ID.
     * @return Collection<FixedCostOccurrence>
     *
     * @throws ModelNotFoundException If the user has not completed onboarding.
     */
    public function execute(int $userId): Collection
    {
        $snapshot = UserBudgetSnapshot::query()
            ->where('user_id', $userId)
            ->firstOrFail(['cycle_start_date', 'cycle_end_date']);

        return FixedCostOccurrence::query()
            ->where('user_id', $userId)
            ->where(function (Builder $query) use ($snapshot) {
                $query->whereBetween('due_date', [
                    $snapshot->cycle_start_date,
                    $snapshot->cycle_end_date,
                ])
                    ->orWhere(function (Builder $subQuery) use ($snapshot) {
                        $subQuery->where('due_date', '<', $snapshot->cycle_start_date)
                            ->whereIn('status', [
                                FixedCostOccurenceStatus::OVERDUE,
                            ]);
                    });
            })
            ->orderBy('due_date')
            ->get();
    }
}
