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
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateIncomeTransactionAction
{
    public function __construct(
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
        private readonly TransactionValidationService $transactionValidationService,
    ) {}

    /**
     * Rule 19: Income transaction adds to balance and triggers allowance recalculation.
     *
     * @throws BusinessRuleException|Throwable
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

            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'category_id' => $data->categoryId,
                'category_type' => $categoryType,
                'name' => $data->name,
                'amount' => $amount,
                'type' => TransactionType::INCOME->value,
                'note' => $data->note,
                'transaction_at' => $data->transactionAt,
                'source' => TransactionSource::MANUAL->value,
            ]);

            // trigger allowance recalculation after income is recorded
            $this->recalculateBudgetSnapshotAction->execute(
                userId: $user->id,
                now: CarbonImmutable::now(),
            );

            return $transaction;
        });
    }
}
