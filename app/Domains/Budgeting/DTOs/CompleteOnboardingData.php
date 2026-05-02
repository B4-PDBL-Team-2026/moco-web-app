<?php

namespace App\Domains\Budgeting\DTOs;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCost\DTOs\CreateFixedCostTemplateData;

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

    public function hasFixedCosts(): bool
    {
        return $this->fixedCosts != [];
    }
}
