<?php

namespace App\Domains\Transaction\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\ValueObjects\Money;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\TransactionValidator;
use App\Domains\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateIncomeTransactionAction
{
    public function __construct(
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
        private readonly TransactionValidator $transactionValidationService,
    ) {}

    /**
     * @throws Throwable
     * @throws BusinessRuleException
     */
    public function execute(User $user, CreateTransactionData $data): Transaction
    {
        $this->transactionValidationService->validateCreate($user, $data);

        return DB::transaction(function () use ($user, $data) {
            $amount = Money::normalize($data->amount);

            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'category_id' => $data->categoryId,
                'name' => $data->name,
                'amount' => $amount,
                'type' => TransactionType::INCOME->value,
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
