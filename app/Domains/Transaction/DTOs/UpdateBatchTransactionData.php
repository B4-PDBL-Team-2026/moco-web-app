<?php

namespace App\Domains\Transaction\DTOs;

use App\Domains\Transaction\Enums\TransactionSource;
use Carbon\CarbonImmutable;

final readonly class UpdateBatchTransactionData
{
    /**
     * @param  array<UpdateBatchTransactionItemData>  $items
     */
    public function __construct(
        public string $name,
        public ?string $note,
        public CarbonImmutable $transactionAt,
        public ?TransactionSource $source,
        public array $items,
    ) {}
}
