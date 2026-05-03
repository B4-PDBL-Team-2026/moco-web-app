<?php

namespace App\Domains\FixedCost\Actions;

use App\Commons\ValueObjects\Money;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Confirms the payment of a fixed cost occurrence.
 *
 * Business rules implemented:
 * - BR §13: Rejects if user's current balance < occurrence amount.
 * - BR §14: Deducts amount from balance, reduces reserved cost, triggers recalculation.
 * - Creates an expense Transaction linked to this occurrence for audit trail.
 *
 * This action is intentionally free of balance/snapshot math — it delegates
 * that responsibility to RecalculateBudgetSnapshotAction.
 */
final readonly class ConfirmFixedCostPaymentAction
{
    public function __construct(
        private RecalculateBudgetSnapshotAction $recalculateBudgetSnapshot,
    ) {}

    /**
     * @param  int  $userId  The authenticated user's ID.
     * @param  int  $occurrenceId  The occurrence being paid.
     *
     * @throws ModelNotFoundException If the occurrence does not belong to the user.
     * @throws InvalidArgumentException If the balance is insufficient (BR §13).
     * @throws Throwable If the DB transaction fails.
     */
    public function execute(int $userId, int $occurrenceId): FixedCostOccurrence
    {
        $occurrence = FixedCostOccurrence::query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                FixedCostOccurenceStatus::PENDING->value,
                FixedCostOccurenceStatus::OVERDUE->value,
            ])
            ->findOrFail($occurrenceId);

        $snapshot = UserBudgetSnapshot::query()
            ->where('user_id', $userId)
            ->firstOrFail();

        // reject if balance < occurrence amount.
        if (Money::lt((string) $snapshot->current_balance, (string) $occurrence->amount)) {
            throw new InvalidArgumentException(
                'Insufficient balance to confirm this fixed cost payment.'
            );
        }

        return DB::transaction(function () use ($userId, $occurrence): FixedCostOccurrence {
            $occurrence->update([
                'status' => FixedCostOccurenceStatus::PAID->value,
                'paid_at' => now(),
            ]);

            // Create a linked expense transaction for the audit trail.
            Transaction::query()->create([
                'user_id' => $userId,
                'category_id' => $occurrence->category_id,
                'fixed_cost_occurrence_id' => $occurrence->id,
                'type' => TransactionType::EXPENSE->value,
                'source' => TransactionSource::FIXED_COST_PAYMENT->value,
                'name' => $occurrence->name,
                'amount' => $occurrence->amount,
                'transaction_at' => CarbonImmutable::today()->toDateString(),
                'effective_at' => now(),
            ]);

            // Delegate all balance/reserved cost/daily allowance recalculation.
            $this->recalculateBudgetSnapshot->execute($userId);

            return $occurrence->refresh();
        });
    }
}
