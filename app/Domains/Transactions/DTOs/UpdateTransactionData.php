<?php

namespace App\Domains\Transactions\DTOs;

use Carbon\CarbonImmutable;

final readonly class UpdateTransactionData
{
    public function __construct(
        public bool $nameProvided,
        public ?string $name,

        public bool $amountProvided,
        public ?string $amount,

        public bool $categoryIdProvided,
        public ?int $categoryId,

        public bool $categoryTypeProvided,
        public ?string $categoryType,

        public bool $noteProvided,
        public ?string $note,

        public bool $transactionAtProvided,
        public ?CarbonImmutable $transactionAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nameProvided: array_key_exists('name', $data),
            name: $data['name'] ?? null,

            amountProvided: array_key_exists('amount', $data),
            amount: array_key_exists('amount', $data) ? (string) $data['amount'] : null,

            categoryIdProvided: array_key_exists('categoryId', $data),
            categoryId: array_key_exists('categoryId', $data) && $data['categoryId'] !== null
                ? (int) $data['categoryId']
                : null,

            categoryTypeProvided: array_key_exists('categoryType', $data),
            categoryType: array_key_exists('categoryType', $data) && $data['categoryType'] !== null
                ? (string) $data['categoryType']
                : null,

            noteProvided: array_key_exists('note', $data),
            note: $data['note'] ?? null,

            transactionAtProvided: array_key_exists('transactionAt', $data),
            transactionAt: array_key_exists('transactionAt', $data)
                ? CarbonImmutable::parse($data['transactionAt'])->utc()
                : null,
        );
    }
}
