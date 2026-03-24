<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\Models\User;
use App\Enums\TransactionType;
use App\Services\TransactionBalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
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

            if ($transaction->type === TransactionType::INCOME) {
                $balance = $user->balance;
                $incomeAmount = $transaction->amount;

                if (bccomp((string)$balance, (string)$incomeAmount, 2) === -1) {
                    throw ValidationException::withMessages([
                        'amount' => 'Cannot delete income because balance would become negative.',
                    ]);
                }
            }

            $user->update([
                'balance' => $newBalance,
            ]);

            $transaction->delete();
        });
    }
}
