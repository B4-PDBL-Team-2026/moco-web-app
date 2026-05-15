<?php

namespace App\Domains\Transaction\DTOs;

use App\Domains\Transaction\Enums\TransactionType;

final readonly class CreateBatchTransactionData
{
    /**
     * @param  array<CreateBatchTransactionItemData>  $items
     */
    public function __construct(
        public string $name,
        public TransactionType $type,
        public string $transactionAt,
        public array $items,
    ) {}
}
