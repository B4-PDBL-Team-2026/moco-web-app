<?php

namespace App\Domains\FixedCost\DTOs;

use App\Domains\Budgeting\Enums\CycleType;

class CreateFixedCostTemplateData
{
    public function __construct(
        public string $name,
        public string $amount,
        public CycleType $cycleType,
        public int $dueDay,
        public bool $isActive,
        public int $categoryId,
    ) {}
}
