<?php

namespace App\Domains\Transaction\Actions;

use App\Commons\ValueObjects\Money;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Budgeting\Services\TransactionBalanceService;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\TransactionValidator;
use App\Domains\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateTransactionAmountAction
{
    public function __construct(
        private readonly TransactionBalanceService $transactionBalanceService,
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
        private readonly TransactionValidator $transactionValidationService,
    ) {}

    /**
     * Rule 23: Updating amount triggers balance and allowance recalculation.
     * Rule 24: Updated expense amount must not cause balance to go negative.
     * Rule 1:  Balance must never go below zero.
     *
     * @throws ValidationException|Throwable
     */
    public function execute(User $user, Transaction $transaction, string $newAmount): Transaction
    {
        return DB::transaction(function () use ($user, $transaction, $newAmount) {
            $normalizedAmount = Money::normalize($newAmount);

            // Calculate what the balance would be after applying the update
            $snapshot = UserBudgetSnapshot::where('user_id', $user->id)->firstOrFail();
            $currentBalance = (string) $snapshot->current_balance;

            $projectedBalance = $this->transactionBalanceService->reapplyUpdatedTransaction(
                currentBalance: $currentBalance,
                oldTransaction: $transaction,
                newType: $transaction->type,
                newAmount: $normalizedAmount,
            );

            // reject if update causes balance to go negative
            $this->transactionValidationService->ensureSufficientBalance($projectedBalance, '0');

            $transaction->update(['amount' => $normalizedAmount]);

            // trigger recalculation after amount change
            $this->recalculateBudgetSnapshotAction->execute(
                userId: $user->id,
                now: CarbonImmutable::now(),
            );

            return $transaction->refresh();
        });
    }
}
