<?php

namespace App\Domains\Transactions\Services;

use App\Commons\Services\MoneyService;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Transaction;

class UserBalanceCalculator
{
    public function calculateCurrentBalance(int $userId): string
    {
        $totalIncome = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionType::INCOME->value)
            ->sum('amount') ?? 0;

        $totalExpense = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionType::EXPENSE->value)
            ->sum('amount') ?? 0;

        return MoneyService::sub((string) $totalIncome, (string) $totalExpense, 2);
    }
}
