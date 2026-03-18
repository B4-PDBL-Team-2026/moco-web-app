<?php

namespace App\Domains\FixedCosts\DTOs;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Budgeting\Enums\DeductionType;

class FixedCostDTO
{
    public function __construct(
        public string $name,
        public float $amount,
        public DeductionType $deductionType,
        public CycleType $cycle,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            amount: (float) $data['amount'],
            deductionType: DeductionType::from($data['deductionType']),
            cycle: isset($data['cycle']) ?
                CycleType::from($data['cycle'])
                : CycleType::MONTHLY,
        );
    }
}
