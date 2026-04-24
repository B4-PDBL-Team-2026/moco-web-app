<?php

namespace App\Domains\Transactions\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\Services\MoneyService;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Domains\Transactions\Services\TransactionValidator;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateExpenseTransactionAction
{
    public function __construct(
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
        private readonly TransactionValidator $transactionValidationService,
    ) {}

    /**
     * @throws \Throwable
     * @throws BusinessRuleException
     */
    public function execute(User $user, CreateTransactionData $data): Transaction
    {
        $this->transactionValidationService->validateCreate($user, $data);

        return DB::transaction(function () use ($user, $data) {
            $amount = MoneyService::normalize($data->amount);

            $snapshot = UserBudgetSnapshot::where('user_id', $user->id)->firstOrFail();
            $this->transactionValidationService->ensureSufficientBalance(
                balance: (string) $snapshot->current_balance,
                amount: $amount
            );

            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'category_id' => $data->categoryId,
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
