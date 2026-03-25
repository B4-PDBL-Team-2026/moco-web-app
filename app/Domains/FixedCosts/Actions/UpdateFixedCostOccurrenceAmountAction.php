<?php

namespace App\Domains\FixedCosts\Actions;

use App\Commons\Services\MoneyService;
use App\Domains\FixedCosts\DTOs\UpdateFixedCostOccurrenceAmountData;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Updates the amount of a specific fixed cost occurrence.
 *
 * Business Rule §17 mandates a strict three-step flow for changing the amount
 * of an already-paid occurrence:
 *   1. Cancel payment  → status becomes VOID, transaction soft-deleted, snapshot recalculated.
 *   2. Edit occurrence amount (this action).
 *   3. Confirm payment → new transaction created, snapshot recalculated again.
 *
 * This action therefore only accepts occurrences in VOID status, enforcing
 * that the caller has already gone through step 1 before reaching step 2.
 *
 * For pending/overdue occurrences the same constraint applies — callers must
 * have the occurrence in VOID state before editing amount, ensuring the
 * reserved-cost accounting stays consistent throughout.
 *
 * After this action completes the occurrence is ready for re-confirmation
 * via ConfirmFixedCostPaymentAction.
 */
final readonly class UpdateFixedCostOccurrenceAmountAction
{
    /**
     * @param  int  $userId  Ownership guard.
     * @param  int  $occurrenceId  The occurrence to edit.
     * @param  UpdateFixedCostOccurrenceAmountData  $data  New amount payload.
     *
     * @throws ModelNotFoundException If the occurrence does not belong to the user.
     * @throws InvalidArgumentException If the occurrence is not in VOID state or amount is invalid.
     * @throws Throwable If the DB write fails.
     */
    public function execute(
        int $userId,
        int $occurrenceId,
        UpdateFixedCostOccurrenceAmountData $data,
    ): void {
        if (MoneyService::lte($data->amount, '0')) {
            throw new InvalidArgumentException('Occurrence amount must be greater than zero.');
        }

        // BR §17: only voided occurrences may have their amount edited.
        $occurrence = FixedCostOccurrence::query()
            ->where('user_id', $userId)
            ->where('status', FixedCostOccurenceStatus::VOID->value)
            ->findOrFail($occurrenceId);

        DB::transaction(function () use ($occurrence, $data): void {
            $occurrence->update([
                'amount' => $data->amount,
                // Reset void timestamps so re-confirmation creates a clean state.
                'voided_at' => null,
                // Status remains VOID until ConfirmFixedCostPaymentAction is called.
                // This keeps accounting consistent — the occurrence is not reserved
                // and not counted until explicitly re-confirmed.
            ]);
        });
    }
}
