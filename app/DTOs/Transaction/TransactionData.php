<?php

namespace App\DTOs\Transaction;

use Illuminate\Http\Request;

class TransactionData
{
    public function __construct(
        public readonly int $category_id,
        public readonly string $name,
        public readonly float $amount,
        public readonly string $type,
        public readonly ?string $note,
        public readonly string $transaction_date,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            category_id: $request->input('category_id'),
            name: $request->input('name'),
            amount: $request->input('amount'),
            type: $request->input('type'),
            note: $request->input('note'),
            transaction_date: $request->input('transaction_date'),
        );
    }
}