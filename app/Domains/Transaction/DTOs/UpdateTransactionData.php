<?php

namespace App\Domains\Transaction\DTOs;

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

        public bool $noteProvided,
        public ?string $note,

        public bool $transactionAtProvided,
        public ?CarbonImmutable $transactionAt,
    ) {}
}
