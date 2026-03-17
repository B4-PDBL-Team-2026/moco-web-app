<?php

namespace App\DTOs\Onboarding;

use App\DTOs\Budget\FixedCostDTO;
use App\Enums\CycleType;
use App\Http\Requests\Onboarding\StoreOnboardingRequest;

final readonly class StoreOnboardingUserDTO
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
