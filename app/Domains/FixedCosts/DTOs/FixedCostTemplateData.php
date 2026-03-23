<?php

namespace App\Domains\FixedCosts\DTOs;

use App\Domains\Budgeting\Enums\CycleType;

class FixedCostTemplateData
{
    public function __construct(
        public string $name,
        public string $amount,
        public CycleType $cycleType,
        public bool $isActive,
        public int $categoryId,
        public int $dueDay,
        public string $categoryType,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            amount: (string) $data['amount'],
            cycleType: CycleType::from($data['cycleType']),
            isActive: $data['isActive'] ?? true,
            categoryId: $data['categoryId'],
            dueDay: $data['dueDay'],
            categoryType: $data['categoryType'],
        );
    }
}
