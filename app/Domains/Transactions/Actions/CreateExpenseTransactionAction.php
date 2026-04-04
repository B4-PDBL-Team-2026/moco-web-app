<?php

namespace App\Domains\Transactions\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\Services\MoneyService;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Domains\Transactions\Services\TransactionValidationService;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateExpenseTransactionAction
{
    public function __construct(
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
        private readonly TransactionValidationService $transactionValidationService,
    ) {}

    /**
     * Rule 21: Expense may exceed daily allowance but must not exceed current balance.
     * Rule 1:  Balance must never go below zero.
     *
     * @throws ValidationException|Throwable
     */
    public function execute(User $user, CreateTransactionData $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            $amount = MoneyService::normalize($data->amount);

            if ((float) $amount <= 0) {
                throw new BusinessRuleException('Transaction amount must be greater than 0.');
            }

            $categoryType = $this->transactionValidationService->resolveAndEnsureCategoryAllowed(
                categoryType: $data->categoryType,
                categoryId: $data->categoryId,
                transactionType: $data->type->value,
                user: $user,
            );

            $snapshot = UserBudgetSnapshot::where('user_id', $user->id)->firstOrFail();
            $currentBalance = (string) $snapshot->current_balance;

            if (MoneyService::gt($amount, $currentBalance)) {
                throw new BusinessRuleException('Insufficient balance to complete this transaction.');
            }

            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'category_id' => $data->categoryId,
                'category_type' => $categoryType,
                'name' => $data->name,
                'amount' => $amount,
                'type' => TransactionType::EXPENSE->value,
                'note' => $data->note,
                'transaction_at' => $data->transactionAt,
                'source' => TransactionSource::MANUAL->value,
            ]);

            $this->recalculateBudgetSnapshotAction->execute(
                userId: $user->id,
                now: CarbonImmutable::now(),
            );

            return $transaction;
        });
    }
}
