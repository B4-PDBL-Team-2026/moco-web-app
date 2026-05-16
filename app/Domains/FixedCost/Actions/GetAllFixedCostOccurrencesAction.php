<?php

namespace App\Domains\FixedCost\Actions;

use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\FixedCost\DTOs\FilterFixedCostOccurrenceData;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Returns all fixed cost occurrences for the user's current budget cycle.
 *
 * Uses the cycle_key stored in the user's budget snapshot as the reference point,
 * so the result is always consistent with what the snapshot considers "current".
 *
 * Results are ordered by due_date ascending so the consumer sees upcoming
 * bills in chronological order.
 */
final readonly class GetAllFixedCostOccurrencesAction
{
    /**
     * @param  int  $userId  The authenticated user's ID.
     */
    public function execute(int $userId, FilterFixedCostOccurrenceData $filters): LengthAwarePaginator
    {
        $query = FixedCostOccurrence::with(['category'])
            ->where('user_id', $userId);

        // if range filter present
        if ($filters->startDate !== null || $filters->endDate !== null) {
            if ($filters->startDate !== null) {
                $query->where('due_date', '>=', $filters->startDate);
            }
            if ($filters->endDate !== null) {
                $query->where('due_date', '<=', $filters->endDate);
            }
        } else {
            $snapshot = UserBudgetSnapshot::query()
                ->where('user_id', $userId)
                ->firstOrFail(['cycle_start_date', 'cycle_end_date']);

            $query->where(function (Builder $query) use ($snapshot) {
                $query->whereBetween('due_date', [
                    $snapshot->cycle_start_date,
                    $snapshot->cycle_end_date,
                ])->orWhere(function (Builder $subQuery) use ($snapshot) {
                    $subQuery->where('due_date', '<', $snapshot->cycle_start_date)
                        ->whereIn('status', [
                            FixedCostOccurenceStatus::OVERDUE->value,
                            FixedCostOccurenceStatus::PENDING->value,
                        ]);
                });
            });
        }

        if ($filters->keyword) {
            $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('name', $operator, $filters->keyword.'%');
        }

        if ($filters->status) {
            $query->where('status', $filters->status->value);
        }

        return $query
            ->orderBy('due_date')
            ->paginate(
                perPage: $filters->perPage,
                page: $filters->page,
            );
    }
}
