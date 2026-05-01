<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Budgeting\Services\TransactionBalanceService;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\TransactionValidator;
use App\Domains\User\Models\User;
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
