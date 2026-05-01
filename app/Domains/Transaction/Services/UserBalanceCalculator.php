<?php

namespace App\Domains\Transaction\Services;

use App\Commons\ValueObjects\Money;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;

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

        return Money::sub((string) $totalIncome, (string) $totalExpense);
    }
}
