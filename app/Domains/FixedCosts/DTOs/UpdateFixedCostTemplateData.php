<?php

namespace App\Domains\FixedCosts\DTOs;

use App\Domains\Budgeting\Enums\CycleType;

/**
 * Carries validated data for updating an existing fixed cost template.
 * All fields are optional — only provided fields will be applied.
 */
final readonly class UpdateFixedCostTemplateData
{
    public function __construct(
        public ?string $name,
        public ?string $amount,
        public ?CycleType $cycleType,
        public ?int $dueDay,
        public ?bool $isActive,
        public ?int $categoryId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: isset($data['name']) ? trim($data['name']) : null,
            amount: isset($data['amount']) ? (string) $data['amount'] : null,
            cycleType: isset($data['cycleType'])
                ? ($data['cycleType'] instanceof CycleType ? $data['cycleType'] : CycleType::from($data['cycleType']))
                : null,
            dueDay: isset($data['dueDay']) ? (int) $data['dueDay'] : null,
            isActive: isset($data['isActive']) ? (bool) $data['isActive'] : null,
            categoryId: isset($data['categoryId']) ? (int) $data['categoryId'] : null,
        );
    }
}
