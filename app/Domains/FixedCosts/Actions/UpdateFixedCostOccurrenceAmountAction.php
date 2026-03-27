<?php

namespace App\Domains\FixedCosts\Actions;

use App\Commons\Services\MoneyService;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\FixedCosts\DTOs\UpdateFixedCostOccurrenceAmountData;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\Transaction;
use App\Models\UserBudgetSnapshot;
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
    public function __construct(
        private RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction
    ) {}
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
            ->where('status', '!=', FixedCostOccurenceStatus::VOID->value)
            ->findOrFail($occurrenceId);

        $snapshot = UserBudgetSnapshot::query()
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($occurrence->status === FixedCostOccurenceStatus::PAID) {
            $difference = MoneyService::sub($data->amount, (string) $occurrence->amount);

            if (MoneyService::gt($difference, '0.00')) {
                if (MoneyService::lt((string) $snapshot->current_balance, $difference)) {
                    throw new InvalidArgumentException(
                        'Insufficient balance to increase the amount of an already paid occurrence.'
                    );
                }
            }
        }

        DB::transaction(function () use ($occurrence, $userId, $data): void {
            $occurrence->update([
                'amount' => $data->amount,
            ]);

            if ($occurrence->status === FixedCostOccurenceStatus::PAID->value) {
                if ($occurrence->transaction()->exists()) {
                    $occurrence->transaction()->update([
                        'amount' => $data->amount,
                    ]);
                }
            }

            Transaction::query()->where('fixed_cost_occurrence_id', '=', $occurrence->id)
                ->update(['amount' => $data->amount]);

            $this->recalculateBudgetSnapshotAction->execute($userId);
        });
    }
}
