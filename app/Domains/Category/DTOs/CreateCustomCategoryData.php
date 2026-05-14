<?php

namespace App\Domains\Category\DTOs;

use App\Domains\Transaction\Enums\TransactionType;

final readonly class CreateCustomCategoryData
{
    public function __construct(
        public string $name,
        public string $icon,
        public TransactionType $type,
    ) {}
}
