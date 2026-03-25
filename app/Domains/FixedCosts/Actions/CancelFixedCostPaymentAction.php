<?php

namespace App\Domains\FixedCosts\Actions;

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Cancels a previously confirmed or overdue fixed cost occurrence.
 *
 * Business rules implemented:
 * - BR §11: Sets status to VOID on cancel.
 * - BR §14: Reserved cost is reduced on cancel, triggering daily allowance recalculation.
 * - BR §17: Supports the cancel → edit → re-confirm flow for already-paid occurrences.
 * - BR §19: Past-cycle paid items can also be canceled via this action.
 *
 * The linked expense transaction (if any) is soft-deleted to reverse the balance impact.
 * All numerical recalculation is delegated to RecalculateBudgetSnapshotAction.
 */
final readonly class CancelFixedCostPaymentAction
{
    public function __construct(
        private RecalculateBudgetSnapshotAction $recalculateBudgetSnapshot,
    ) {}

    /**
     * @param  int  $userId  The authenticated user's ID.
     * @param  int  $occurrenceId  The occurrence to cancel.
     *
     * @throws ModelNotFoundException If the occurrence does not belong to the user.
     * @throws InvalidArgumentException If the occurrence is already void or in an un-cancellable state.
     * @throws Throwable If the DB transaction fails.
     */
    public function execute(int $userId, int $occurrenceId): void
    {
        $occurrence = FixedCostOccurrence::query()
            ->where('user_id', $userId)
            ->whereNotIn('status', [FixedCostOccurenceStatus::VOID->value])
            ->findOrFail($occurrenceId);

        DB::transaction(function () use ($userId, $occurrence): void {
            $occurrence->update([
                'status' => FixedCostOccurenceStatus::VOID->value,
                'voided_at' => now(),
                'paid_at' => null,
            ]);

            // Soft-delete the linked expense transaction (if paid) to reverse balance impact.
            // The transaction model uses SoftDeletes, so this is non-destructive.
            if ($occurrence->transaction()->exists()) {
                $occurrence->transaction()->delete();
            }

            // Recalculate balance, reserved cost, and daily allowance (BR §14).
            $this->recalculateBudgetSnapshot->execute($userId);
        });
    }
}
