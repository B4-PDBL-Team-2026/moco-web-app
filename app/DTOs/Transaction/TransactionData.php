<?php
// app/DTOs/Transaction/TransactionData.php

namespace App\DTOs\Transaction;

use Illuminate\Http\Request;

class TransactionData
{
    public function __construct(
        public readonly string $name,
        public readonly float $amount,
        public readonly string $type,
        public readonly int $category_id,
        public readonly string $transaction_date,
        public readonly ?string $note
    ) {}

    /**
     * Buat DTO dari request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            amount: (float) $request->input('amount'),
            type: $request->input('type'),
            category_id: (int) $request->input('category_id'),
            transaction_date: $request->input('transaction_date'),
            note: $request->input('note'),
        );
    }

    /**
     * Konversi ke array untuk update/create.
     */
    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'amount'      => $this->amount,
            'type'        => $this->type,
            'category_id' => $this->category_id,
            'created_at'  => $this->transaction_date,
            'note'        => $this->note,
        ];
    }
}