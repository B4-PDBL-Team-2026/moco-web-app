<?php

namespace App\Domains\Transaction\DTOs;

use App\Domains\Transaction\Enums\TransactionSource;

final readonly class CreateBatchTransactionData
{
    /**
     * @param  array<CreateBatchTransactionItemData>  $items
     */
    public function __construct(
        public string $name,
        public ?string $note,
        public string $transactionAt,
        public TransactionSource $source,
        public array $items,
    ) {}
}
