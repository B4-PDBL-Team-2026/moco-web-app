<?php

namespace App\Domains\FixedCosts\Actions;

use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Soft-deletes a fixed cost template.
 *
 * Business rules implemented:
 * - The template is soft-deleted so paid occurrences retain their FK reference
 *   and historical record (no paid data is ever destroyed).
 * - Pending and overdue occurrences that have not been settled are voided,
 *   since they no longer have an active template backing them.
 * - Void and already-voided occurrences are left untouched.
 *
 * The cascadeOnDelete on the DB FK only fires for hard-deletes; soft-deletes
 * leave child records intact, which is the desired behavior here.
 */
final readonly class DeleteFixedCostTemplateAction
{
    /**
     * @param  int  $userId  The authenticated user's ID (ownership check).
     * @param  int  $templateId  The template to delete.
     *
     * @throws ModelNotFoundException If the template does not belong to the user.
     * @throws Throwable If the DB transaction fails.
     */
    public function execute(int $userId, int $templateId): void
    {
        $template = FixedCostTemplate::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->findOrFail($templateId);

        DB::transaction(function () use ($template): void {
            // Void any unsettled occurrences so they are no longer actionable.
            // Paid occurrences are intentionally left intact for audit purposes.
            FixedCostOccurrence::query()
                ->where('fixed_cost_template_id', $template->id)
                ->whereIn('status', [
                    FixedCostOccurenceStatus::PENDING->value,
                    FixedCostOccurenceStatus::OVERDUE->value,
                ])
                ->update([
                    'status' => FixedCostOccurenceStatus::VOID->value,
                    'voided_at' => now(),
                ]);

            $template->delete();
        });
    }
}
