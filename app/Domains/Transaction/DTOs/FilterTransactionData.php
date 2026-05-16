<?php

namespace App\Domains\Transaction\DTOs;

use App\Domains\Transaction\Enums\TransactionFeedType;
use App\Domains\Transaction\Enums\TransactionType;

final readonly class FilterTransactionData
{
    public function __construct(
        public ?int $month,
        public ?int $year,
        public ?string $search,
        public ?int $categoryId,
        public int $perPage,
        public ?TransactionType $transactionType,
        public ?TransactionFeedType $transactionFeedType,
    ) {}
}
