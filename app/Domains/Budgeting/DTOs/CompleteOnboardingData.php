<?php

namespace App\Domains\Budgeting\DTOs;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\DTOs\CreateFixedCostTemplateData;

final readonly class CompleteOnboardingData
{
    /**
     * @param  list<CreateFixedCostTemplateData>  $fixedCosts
     */
    public function __construct(
        public CycleType $cycleType,
        public string $initialBalance,
        public string $flooringLimit,
        public string $ceilingLimit,
        public array $fixedCosts = [],
        public string $timezone = 'Asia/Jakarta',
    ) {}

    public static function fromData(array $data): self
    {
        return new self(
            cycleType: CycleType::from($data['budgetCycle']),
            initialBalance: (string) $data['initialBalance'],
            flooringLimit: (string) $data['flooringLimit'],
            ceilingLimit: (string) $data['ceilingLimit'],
            fixedCosts: array_map(
                fn (array $fixedCost) => CreateFixedCostTemplateData::fromArray($fixedCost),
                $data['fixedCosts'] ?? []
            ),
            timezone: $data['timezone'],
        );
    }

    public function hasFixedCosts(): bool
    {
        return $this->fixedCosts != [];
    }
}
