<?php
// app/Actions/Transaction/CreateTransactionAction.php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\DTOs\Transaction\TransactionData;
use Illuminate\Support\Facades\Auth;

class CreateTransactionAction
{
    public function execute(TransactionData $data): Transaction
    {
        return Transaction::create([
            'name'        => $data->name,
            'amount'      => $data->amount,
            'type'        => $data->type,
            'note'        => $data->note,
            'user_id'     => Auth::id(),
            'category_id' => $data->category_id,
            'created_at'  => $data->transaction_date,
        ]);
    }
}