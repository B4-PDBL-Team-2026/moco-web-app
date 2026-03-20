<?php

namespace App\Domains\Transactions\Actions;

use App\Domains\Budgeting\Services\TransactionBalanceService;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Throwable;

class DeleteTransactionAction
{
    public function __construct(
        private readonly TransactionBalanceService $transactionBalanceService
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(User $user, Transaction $transaction): void
    {
        DB::transaction(function () use ($user, $transaction) {
            if ($user->id !== $transaction->user_id) {
                throw new UnauthorizedException('You are not authorized to perform this action.');
            }

            $newBalance = $this->transactionBalanceService->reverseTransaction(
                currentBalance: (string) $user->balance,
                type: $transaction->type,
                amount: (string) $transaction->amount
            );

            $user->update([
                'balance' => $newBalance,
            ]);

            $transaction->delete();
        });
    }
}
