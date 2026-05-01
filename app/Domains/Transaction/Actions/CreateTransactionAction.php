<?php

namespace App\Domains\Transaction\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
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
            TransactionType::INCOME => $this->createIncomeTransactionAction->execute($user, $dto),
            TransactionType::EXPENSE => $this->createExpenseTransactionAction->execute($user, $dto),
        };
    }
}
