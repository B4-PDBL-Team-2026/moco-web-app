<?php

namespace App\DTOs\Transaction;

use App\Enums\TransactionType;

final readonly class CreateTransactionData
{
    public function __construct(
        public int $categoryId,
        public string $name,
        public float $amount,
        public TransactionType $type,
        public ?string $note,
        public string $transactionDate,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            categoryId: $data['category_id'],
            name: $data['name'],
            amount: $data['amount'],
            type: TransactionType::from($data['type']),
            note: $data['note'],
            transactionDate: $data['transaction_date'],
        );
    }
}
