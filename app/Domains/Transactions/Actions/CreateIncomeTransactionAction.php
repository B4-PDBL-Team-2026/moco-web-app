<?php

namespace App\Domains\Transactions\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\Services\MoneyService;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\SystemCategory;
use App\Models\CustomCategory;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateIncomeTransactionAction
{
    public function __construct(
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
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

             // resolve category
            if ($dto->categoryType === 'system') {
                $category = SystemCategory::findOrFail($dto->categoryId);
                $categoryType = SystemCategory::class;
            } else {
                $category = CustomCategory::where('id', $dto->categoryId)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $categoryType = CustomCategory::class;
            }

            // validate category type matches transaction type
            if ($category->type !== $dto->type->value) {
                throw ValidationException::withMessages([
                    'categoryId' => ['Category type does not match transaction type.'],
                ]);
            }
            
            $transaction = Transaction::query()->create([
                'user_id'          => $user->id,
                'category_id'      => $dto->categoryId,
                'category_type'    => $categoryType,
                'name'             => $dto->name,
                'amount'           => $amount,
                'type'             => TransactionType::INCOME->value,
                'note'             => $dto->note,
                'transaction_date' => $dto->transactionDate->toDateString(),
                'source'           => TransactionSource::MANUAL->value,
            ]);

            // Rule 19: trigger allowance recalculation after income is recorded
            $this->recalculateBudgetSnapshotAction->execute(
                userId: $user->id,
                now: CarbonImmutable::now(),
            );

            return $transaction;
        });
    }
}