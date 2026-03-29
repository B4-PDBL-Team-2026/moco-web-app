<?php

namespace App\Domains\Transactions\DTOs;

use App\Domains\Transactions\Enums\TransactionType;
use Carbon\CarbonImmutable;

final readonly class CreateTransactionData
{
    public function __construct(
        public int $categoryId,
        public string $categoryType,
        public string $name,
        public string $amount,
        public TransactionType $type,
        public ?string $note,
        public CarbonImmutable $transactionDate,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            categoryId: (int) $data['categoryId'],
            categoryType: $data['categoryType'] ?? 'system',
            name: $data['name'],
            amount: (string) $data['amount'],
            type: TransactionType::from($data['type']),
            note: $data['note'] ?? null,
            transactionDate: CarbonImmutable::parse($data['transactionDate']),
        );
    }
}
