<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
use Illuminate\Validation\UnauthorizedException;

class GetTransactionDetailAction
{
    public function execute(User $user, Transaction $transaction): Transaction
    {
        if ($transaction->user_id !== $user->id) {
            throw new UnauthorizedException('Transaction not found or unauthorized.');
        }

        return $transaction->load('category');
    }
}
