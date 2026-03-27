<?php

namespace App\Domains\Transactions\Actions;

use App\Commons\Services\MoneyService;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Services\TransactionBalanceService;
use App\Domains\Transactions\Enums\TransactionType;
use App\Domains\Transactions\Services\UserBalanceCalculator;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeleteTransactionAction
{
    public function __construct(
        private readonly TransactionBalanceService $transactionBalanceService,
        private readonly UserBalanceCalculator $userBalanceCalculator,
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
    ) {}

    /**
     * Rule 28: Expense transactions can always be deleted (triggers recalculation).
     *          Income transactions can only be deleted if balance - amount >= 0.
     *
     * @throws ValidationException|UnauthorizedException|Throwable
     */
    public function execute(User $user, Transaction $transaction): void
    {
        DB::transaction(function () use ($user, $transaction) {
            if ($user->id !== $transaction->user_id) {
                throw new UnauthorizedException('You are not authorized to perform this action.');
            }

            // Rule 28: income deletion requires balance check
            if ($transaction->type === TransactionType::INCOME) {
                $currentBalance = $this->userBalanceCalculator->calculateCurrentBalance($user->id);

                $balanceAfterDeletion = $this->transactionBalanceService->reverseTransaction(
                    currentBalance: $currentBalance,
                    type: $transaction->type,
                    amount: (string) $transaction->amount,
                );

                // Rule 28 + Rule 1: reject if deletion causes balance to go negative
                if (MoneyService::lt($balanceAfterDeletion, '0.00')) {
                    throw ValidationException::withMessages([
                        'transaction' => ['Cannot delete this income transaction as it would cause the balance to go negative.'],
                    ]);
                }
            }

            $transaction->delete();

            // Rule 28: trigger recalculation after deletion
            $this->recalculateBudgetSnapshotAction->execute(
                userId: $user->id,
                now: CarbonImmutable::now(),
            );
        });
    }
}