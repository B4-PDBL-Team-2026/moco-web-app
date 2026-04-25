<?php

namespace App\Domains\Transactions\Actions;

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Services\TransactionBalanceService;
use App\Domains\Transactions\Enums\TransactionType;
use App\Domains\Transactions\Services\TransactionValidator;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeleteTransactionAction
{
    public function __construct(
        private readonly TransactionBalanceService $transactionBalanceService,
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
        private readonly TransactionValidator $transactionValidationService,
    ) {}

    /**
     * Rule 28: Expense transactions can always be deleted (triggers recalculation).
     *          Income transactions can only be deleted if balance - amount >= 0.
     *
     * @throws ValidationException|UnauthorizedException|Throwable
     */
    public function execute(User $user, Transaction $transaction): void
    {
        if ($user->id !== $transaction->user_id) {
            throw new UnauthorizedException('You are not authorized to perform this action.');
        }

        DB::transaction(function () use ($user, $transaction) {
            // income deletion requires balance check
            if ($transaction->type === TransactionType::INCOME) {
                $snapshot = UserBudgetSnapshot::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentBalance = (string) $snapshot->current_balance;

                $balanceAfterDeletion = $this->transactionBalanceService->reverseTransaction(
                    currentBalance: $currentBalance,
                    type: $transaction->type,
                    amount: (string) $transaction->amount,
                );

                // reject if deletion causes balance to go negative
                $this->transactionValidationService->ensureSufficientBalance($balanceAfterDeletion, '0.00');
            }

            $transaction->delete();

            // trigger recalculation after deletion
            $this->recalculateBudgetSnapshotAction->execute(
                userId: $user->id,
                now: CarbonImmutable::now(),
            );
        });
    }
}
