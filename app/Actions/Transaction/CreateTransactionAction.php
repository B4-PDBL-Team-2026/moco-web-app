<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\DTOs\Transaction\TransactionData;
use Illuminate\Support\Facades\Auth;

class CreateTransactionAction
{
    public function execute(TransactionData $data): Transaction
    {
        return Transaction::create([
            'user_id'     => auth::id(),
            'category_id' => $data->category_id,
            'name'        => $data->name,
            'amount'      => $data->amount,
            'type'        => $data->type,
            'note'        => $data->note,
        ]);
    }
};