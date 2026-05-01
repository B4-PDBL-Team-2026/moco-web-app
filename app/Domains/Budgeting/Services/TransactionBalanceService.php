<?php

namespace App\Domains\Budgeting\Services;

use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;

class TransactionBalanceService
{
    public function applyTransaction(string $currentBalance, TransactionType $type, string $amount): string
    {
        return match ($type) {
            TransactionType::INCOME => bcadd($currentBalance, $amount, 2),
            TransactionType::EXPENSE => bcsub($currentBalance, $amount, 2),
        };
    }

    public function reverseTransaction(string $currentBalance, TransactionType $type, string $amount): string
    {
        return match ($type) {
            TransactionType::INCOME => bcsub($currentBalance, $amount, 2),
            TransactionType::EXPENSE => bcadd($currentBalance, $amount, 2),
        };
    }

    public function reapplyUpdatedTransaction(
        string $currentBalance,
        Transaction $oldTransaction,
        TransactionType $newType,
        string $newAmount
    ): string {
        $balanceAfterRevert = $this->reverseTransaction(
            $currentBalance,
            $oldTransaction->type,
            (string) $oldTransaction->amount,
        );

        return $this->applyTransaction(
            $balanceAfterRevert,
            $newType,
            $newAmount,
        );
    }
}
