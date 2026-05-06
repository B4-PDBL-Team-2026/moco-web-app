<?php

namespace App\Domains\FixedCost\Actions;

use App\Domains\FixedCost\DTOs\FilterFixedCostTemplateData;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Returns a paginated list of fixed cost templates for a user,
 * with optional filters on name, due_day, cycle_type, and is_active.
 */
class GetAllFixedCostTemplatesAction
{
    /**
     * @param  int  $userId  The authenticated user's ID.
     * @param  FilterFixedCostTemplateData  $filters  Filter and pagination params.
     * @return LengthAwarePaginator<FixedCostTemplate>
     */
    public function execute(int $userId, FilterFixedCostTemplateData $filters): LengthAwarePaginator
    {
        $query = FixedCostTemplate::with(['category'])
            ->where('user_id', $userId)
            ->where('deleted_at', null);

        if ($filters->keyword !== null) {
            $query->where('name', 'like', $filters->keyword.'%');
        }

        if ($filters->dueDay !== null) {
            $query->where('due_day', $filters->dueDay);
        }

        if ($filters->cycleType !== null) {
            $query->where('cycle_type', $filters->cycleType->value);
        }

        if ($filters->isActive !== null) {
            $query->where('is_active', $filters->isActive);
        }

        return $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(
                perPage: $filters->perPage,
                page: $filters->page,
            );
    }
}
