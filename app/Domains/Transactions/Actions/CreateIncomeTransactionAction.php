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
    public function execute(User $user, CreateTransactionData $dto): Transaction
    {
        return DB::transaction(function () use ($user, $dto) {
            $amount = MoneyService::normalize($dto->amount);

            $categoryType = $this->transactionValidationService->resolveAndEnsureCategoryAllowed(
                categoryType: $dto->categoryType,
                categoryId: $dto->categoryId,
                transactionType: $dto->type->value,
                user: $user,
            );

            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'category_id' => $dto->categoryId,
                'category_type' => $categoryType,
                'name' => $dto->name,
                'amount' => $amount,
                'type' => TransactionType::INCOME->value,
                'note' => $dto->note,
                'transaction_date' => $dto->transactionDate->toDateString(),
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
