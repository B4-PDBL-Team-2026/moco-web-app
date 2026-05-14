<?php

namespace App\Domains\Category\DTOs;

use App\Domains\Transaction\Enums\TransactionType;

final readonly class UpdateCustomCategoryData
{
    public function __construct(
        public ?string $name = null,
        public ?string $icon = null,
        public ?TransactionType $type = null,
    ) {}
}
