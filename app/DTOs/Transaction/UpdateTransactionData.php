<?php

namespace App\DTOs\Transaction;

use App\Enums\TransactionType;
use Carbon\CarbonImmutable;

final readonly class UpdateTransactionData
{
    public function __construct(
        public bool $nameProvided,
        public ?string $name,

        public bool $amountProvided,
        public ?string $amount,

        public bool $typeProvided,
        public ?TransactionType $type,

        public bool $categoryIdProvided,
        public ?int $categoryId,

        public bool $noteProvided,
        public ?string $note,

        public bool $transactionDateProvided,
        public ?CarbonImmutable $transactionDate,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nameProvided: array_key_exists('name', $data),
            name: $data['name'] ?? null,

            amountProvided: array_key_exists('amount', $data),
            amount: array_key_exists('amount', $data) ? (string) $data['amount'] : null,

            typeProvided: array_key_exists('type', $data),
            type: array_key_exists('type', $data)
                ? TransactionType::from($data['type'])
                : null,

            categoryIdProvided: array_key_exists('categoryId', $data),
            categoryId: array_key_exists('categoryId', $data) && $data['categoryId'] !== null
                ? (int) $data['categoryId']
                : null,

            noteProvided: array_key_exists('note', $data),
            note: $data['note'] ?? null,

            transactionDateProvided: array_key_exists('transactionDate', $data),
            transactionDate: array_key_exists('transactionDate', $data)
                ? CarbonImmutable::parse($data['transactionDate'])
                : null,
        );
    }
}
