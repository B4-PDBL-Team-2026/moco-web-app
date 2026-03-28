<?php

namespace App\Domains\Transactions\Actions;

use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Throwable;

class CreateTransactionAction
{
    public function __construct(
        private readonly CreateIncomeTransactionAction $createIncomeAction,
        private readonly CreateExpenseTransactionAction $createExpenseAction,
    ) {}

    /**
     * Dispatches to the appropriate action based on transaction type.
     *
     * @throws Throwable
     */
    public function execute(User $user, CreateTransactionData $dto): Transaction
    {
        return match ($dto->type) {
            TransactionType::INCOME  => $this->createIncomeAction->execute($user, $dto),
            TransactionType::EXPENSE => $this->createExpenseAction->execute($user, $dto),
        };
    }
}