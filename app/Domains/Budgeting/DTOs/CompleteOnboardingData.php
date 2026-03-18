<?php

namespace App\Domains\Budgeting\DTOs;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\DTOs\FixedCostDTO;
use App\Http\Requests\Onboarding\StoreOnboardingRequest;

final readonly class CompleteOnboardingData
{
    public function __construct(
        public CycleType $budgetCycle,
        public float $allowanceAmount,
        public array $fixedCosts = [],
    ) {}

    public static function fromRequest(StoreOnboardingRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            budgetCycle: CycleType::from($validated['budgetCycle']),
            allowanceAmount: (float) $validated['allowanceAmount'],
            fixedCosts: array_map(
                fn (array $fixedCost) => FixedCostDTO::fromArray($fixedCost),
                $validated['fixedCosts'] ?? []
            ),
        );
    }
}
