<?php

namespace App\Domains\Transactions\Actions;

use App\Commons\Services\MoneyService;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Services\TransactionBalanceService;
use App\Domains\Transactions\Services\TransactionValidator;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSnapshot;
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
            $normalizedAmount = MoneyService::normalize($newAmount);

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
