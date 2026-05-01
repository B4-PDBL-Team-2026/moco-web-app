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

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            amount: (string) $data['amount'],
            cycleType: CycleType::from($data['cycleType']),
            dueDay: $data['dueDay'],
            isActive: $data['isActive'] ?? true,
            categoryId: $data['categoryId'],
        );
    }
}
