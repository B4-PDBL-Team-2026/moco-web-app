<?php

namespace App\Domains\FixedCost\DTOs;

use App\Domains\Budgeting\Enums\CycleType;

/**
 * Carries validated data for updating an existing fixed cost template.
 * All fields are optional — only provided fields will be applied.
 */
final readonly class UpdateFixedCostTemplateData
{
    public function __construct(
        public bool $nameProvided,
        public ?string $name,

        public bool $amountProvided,
        public ?string $amount,

        public bool $cycleTypeProvided,
        public ?CycleType $cycleType,

        public bool $dueDayProvided,
        public ?int $dueDay,

        public bool $isActiveProvided,
        public ?bool $isActive,

        public bool $categoryIdProvided,
        public ?int $categoryId,
    ) {}
}
