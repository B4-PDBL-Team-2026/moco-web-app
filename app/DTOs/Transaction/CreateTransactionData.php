<?php

namespace App\DTOs\Transaction;

use App\Enums\TransactionType;
use Carbon\CarbonImmutable;

final readonly class CreateTransactionData
{
    public function __construct(
        public int $categoryId,
        public string $name,
        public float $amount,
        public TransactionType $type,
        public ?string $note,
        public CarbonImmutable $transactionDate,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            categoryId: (int) $data['categoryId'],
            name: $data['name'],
            amount: $data['amount'],
            type: TransactionType::from($data['type']),
            note: $data['note'] ?? null,
            transactionDate: CarbonImmutable::parse($data['transactionDate']),
        );
    }
}
