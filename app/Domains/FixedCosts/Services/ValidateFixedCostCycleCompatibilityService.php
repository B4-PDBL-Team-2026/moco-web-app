<?php

namespace App\Domains\FixedCosts\Services;

use App\Domains\Budgeting\Enums\CycleType;
use InvalidArgumentException;

final readonly class ValidateFixedCostCycleCompatibilityService
{
    public function ensureAllowed(
        CycleType $budgetCycle,
        CycleType $fixedCostCycle,
    ): void {
        if (
            $budgetCycle === CycleType::WEEKLY &&
            $fixedCostCycle === CycleType::MONTHLY
        ) {
            throw new InvalidArgumentException(
                'Monthly fixed cost is not allowed when budget cycle is weekly.'
            );
        }
    }
}
