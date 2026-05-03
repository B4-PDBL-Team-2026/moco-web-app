<?php

namespace App\Domains\FixedCost\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\ValueObjects\Money;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\FixedCost\DTOs\UpdateFixedCostOccurrenceAmountData;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Updates the amount of a specific fixed cost occurrence.
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
    ): FixedCostOccurrence {
        if (Money::lte($data->amount, '0')) {
            throw new InvalidArgumentException('Occurrence amount must be greater than zero.');
        }

        $occurrence = FixedCostOccurrence::query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                FixedCostOccurenceStatus::PENDING->value,
                FixedCostOccurenceStatus::OVERDUE->value,
                FixedCostOccurenceStatus::PAID->value,
            ])
            ->findOrFail($occurrenceId);

        $snapshot = UserBudgetSnapshot::query()
            ->where('user_id', $userId)
            ->firstOrFail();

        // perform balance checking if its paid occurrence
        if ($occurrence->status === FixedCostOccurenceStatus::PAID) {
            $difference = Money::sub($data->amount, (string) $occurrence->amount);

            if (Money::gt($difference, '0.00')) {
                if (Money::lt((string) $snapshot->current_balance, $difference)) {
                    throw new BusinessRuleException('errors.budget.balance_insufficient');
                }
            }
        }

        return DB::transaction(function () use ($occurrence, $userId, $data): FixedCostOccurrence {
            $occurrence->update([
                'amount' => $data->amount,
            ]);

            if ($occurrence->status === FixedCostOccurenceStatus::PAID) {
                Transaction::query()
                    ->where('fixed_cost_occurrence_id', '=', $occurrence->id)
                    ->update(['amount' => $data->amount]);
            }

            // perform budget recalculation
            $this->recalculateBudgetSnapshotAction->execute($userId);

            return $occurrence->refresh();
        });
    }
}
