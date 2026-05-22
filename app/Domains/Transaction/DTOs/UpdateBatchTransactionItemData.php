<?php

namespace App\Domains\Transaction\DTOs;

use App\Domains\Transaction\Enums\TransactionType;

final readonly class UpdateBatchTransactionItemData
{
    public function __construct(
        public string $name,
        public string $amount,
        public int $categoryId,
        public TransactionType $type,
        public ?string $note = null,
    ) {}
}
