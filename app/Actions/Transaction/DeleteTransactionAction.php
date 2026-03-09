<?php
// app/Actions/Transaction/DeleteTransactionAction.php

namespace App\Actions\Transaction;

use App\Models\Transaction;

class DeleteTransactionAction
{
    public function execute(Transaction $transaction): void
    {
        $transaction->delete();

        // Trigger update saldo
    }
}