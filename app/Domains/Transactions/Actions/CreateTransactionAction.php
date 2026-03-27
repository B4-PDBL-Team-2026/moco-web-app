<?php

namespace App\Domains\Transactions\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Throwable;

class CreateTransactionAction
{
    public function __construct(
        private readonly CreateIncomeTransactionAction $createIncomeTransactionAction,
        private readonly CreateExpenseTransactionAction $createExpenseTransactionAction,
    ) {}

    /**
     * Routes to the correct action based on transaction type.
     *
     * @throws BusinessRuleException|Throwable
     */
    public function execute(User $user, CreateTransactionData $dto): Transaction
    {
        return match ($dto->type) {
            TransactionType::INCOME  => $this->createIncomeTransactionAction->execute($user, $dto),
            TransactionType::EXPENSE => $this->createExpenseTransactionAction->execute($user, $dto),
        };
    }
}