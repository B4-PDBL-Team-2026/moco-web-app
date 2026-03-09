<?php
// app/Actions/Transaction/UpdateTransactionAction.php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\DTOs\Transaction\TransactionData;

class UpdateTransactionAction
{
    public function execute(Transaction $transaction, TransactionData $data): Transaction
    {
        $transaction->update([
            'name'        => $data->name,
            'amount'      => $data->amount,
            'type'        => $data->type,
            'note'        => $data->note,
            'category_id' => $data->category_id,
            'created_at'  => $data->transaction_date,
        ]);

        // Anda bisa tambahkan event atau dispatch job untuk update saldo

        return $transaction;
    }
}