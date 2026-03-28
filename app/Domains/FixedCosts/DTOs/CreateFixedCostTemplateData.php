<?php

namespace App\Domains\FixedCosts\DTOs;

use App\Domains\Budgeting\Enums\CycleType;
use InvalidArgumentException;

class CreateFixedCostTemplateData
{
    public function __construct(
        public string $name,
        public string $amount,
        public CycleType $cycleType,
        public bool $isActive,
        public int $categoryId,
        public int $dueDay,
        public string $categoryType,
    ) {
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Fixed cost name is required.');
        }

        if ((float) $this->amount <= 0) {
            throw new InvalidArgumentException('Fixed cost amount must be greater than zero.');
        }

        if ($this->cycleType === CycleType::WEEKLY && ($this->dueDay < 1 || $this->dueDay > 7)) {
            throw new InvalidArgumentException('Weekly due day must be between 1 and 7.');
        }

        if ($this->cycleType === CycleType::MONTHLY && ($this->dueDay < 1 || $this->dueDay > 31)) {
            throw new InvalidArgumentException('Monthly due day must be between 1 and 31.');
        }
    }

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
