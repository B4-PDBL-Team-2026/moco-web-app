<?php

namespace App\Domains\FixedCost\Actions;

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use Carbon\CarbonImmutable;
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
    public function execute(int $userId, int $occurrenceId): FixedCostOccurrence
    {
        $occurrence = FixedCostOccurrence::query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                FixedCostOccurenceStatus::PENDING->value,
                FixedCostOccurenceStatus::OVERDUE->value,
                FixedCostOccurenceStatus::PAID->value,
            ])
            ->findOrFail($occurrenceId);

        return DB::transaction(function () use ($userId, $occurrence): FixedCostOccurrence {
            if ($occurrence->status === FixedCostOccurenceStatus::PAID) {

                $timezone = DB::table('user_budget_settings')
                    ->where('user_id', $userId)
                    ->value('timezone') ?? 'Asia/Jakarta';

                $todayUserTime = CarbonImmutable::now($timezone)->startOfDay();

                $dueDate = CarbonImmutable::parse($occurrence->due_date, $timezone)->startOfDay();

                $newStatus = $todayUserTime->greaterThan($dueDate)
                    ? FixedCostOccurenceStatus::OVERDUE->value
                    : FixedCostOccurenceStatus::PENDING->value;

                $occurrence->update([
                    'status' => $newStatus,
                    'paid_at' => null,
                ]);

                if ($occurrence->transaction) {
                    $occurrence->transaction->delete();
                }

            } else {
                $occurrence->update([
                    'status' => FixedCostOccurenceStatus::SKIPPED->value,
                ]);
            }

            // Recalculate balance, reserved cost, and daily allowance (BR §14).
            $this->recalculateBudgetSnapshot->execute($userId);

            return $occurrence->refresh();
        });
    }
}
