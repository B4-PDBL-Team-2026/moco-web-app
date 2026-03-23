<?php

namespace App\Domains\FixedCosts\Services;

use App\Domains\Budgeting\Enums\CycleType;
use InvalidArgumentException;

/**
 * Enforces business rules regarding the combination of budget cycles and fixed cost frequencies.
 * This validator ensures logical consistency, preventing scenarios such as
 * assigning a monthly fixed cost (e.g., monthly rent) to a user whose
 * budgeting cycle is strictly evaluated on a weekly basis.
 */
final readonly class FixedCostCycleValidator
{
    /**
     * Ensure the provided fixed cost cycle is compatible with the user's budget cycle.
     *
     * @param  CycleType  $budgetCycle  The primary cycle of the user's budget (e.g., WEEKLY, MONTHLY).
     * @param  CycleType  $fixedCostCycle  The billing frequency of the specific fixed cost template.
     *
     * @throws InvalidArgumentException If a monthly fixed cost is applied to a weekly budget window.
     */
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
