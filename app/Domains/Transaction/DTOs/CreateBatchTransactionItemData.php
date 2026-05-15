<?php

namespace App\Domains\Transaction\DTOs;

final readonly class CreateBatchTransactionItemData
{
    public function __construct(
        public string $name,
        public string $amount,
        public int $categoryId,
        public ?string $note = null,
    ) {}
}
