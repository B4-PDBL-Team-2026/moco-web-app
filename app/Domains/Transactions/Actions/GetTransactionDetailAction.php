<?php

namespace App\Domains\Transactions\Actions;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Validation\UnauthorizedException;

class GetTransactionDetailAction
{
    public function execute(User $user, Transaction $transaction): Transaction
    {
        if ($transaction->user_id !== $user->id) {
            throw new UnauthorizedException('Transaction not found or unauthorized.');
        }

        return $transaction;
    }
}
