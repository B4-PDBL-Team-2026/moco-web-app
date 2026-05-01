<?php

namespace App\Domains\Transaction\DTOs;

use App\Domains\Transaction\Enums\TransactionType;
use Carbon\CarbonImmutable;

final readonly class CreateTransactionData
{
    public function __construct(
        public int $categoryId,
        public string $name,
        public string $amount,
        public TransactionType $type,
        public ?string $note,
        public CarbonImmutable $transactionAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            categoryId: (int) $data['categoryId'],
            name: $data['name'],
            amount: (string) $data['amount'],
            type: TransactionType::from($data['type']),
            note: $data['note'] ?? null,
            transactionAt: CarbonImmutable::parse($data['transactionAt'])->utc(),
        );
    }
}
