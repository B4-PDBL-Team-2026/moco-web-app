<?php

namespace App\Domains\FixedCosts\Services;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\DTOs\CreateFixedCostTemplateData;
use App\Models\CustomCategory;
use App\Models\SystemCategory;

/**
 * Enforces business rules regarding the combination of budget cycles and fixed cost frequencies.
 * This validator ensures logical consistency, preventing scenarios such as
 * assigning a monthly fixed cost (e.g., monthly rent) to a user whose
 * budgeting cycle is strictly evaluated on a weekly basis.
 */
final readonly class FixedCostValidator
{
    /**
     * Ensure the provided fixed cost cycle is compatible with the user's budget cycle.
     *
     * @param  CycleType  $budgetCycle  The primary cycle of the user's budget (e.g., WEEKLY, MONTHLY).
     * @param  CycleType  $fixedCostCycle  The billing frequency of the specific fixed cost template.
     *
     * @throws BusinessRuleException If a monthly fixed cost is applied to a weekly budget window.
     */
    public function validateCycleCompatibility(
        CycleType $budgetCycle,
        CycleType $fixedCostCycle,
    ): void {
        if (
            $budgetCycle === CycleType::WEEKLY &&
            $fixedCostCycle === CycleType::MONTHLY
        ) {
            throw new BusinessRuleException(
                'Monthly fixed cost is not allowed when budget cycle is weekly.'
            );
        }
    }

    /**
     * @throws BusinessRuleException
     */
    public function validateFixedCostData(int $userId, CreateFixedCostTemplateData $data, CycleType $userBudgetCycle): void
    {
        $this->validateCycleCompatibility(
            budgetCycle: $userBudgetCycle,
            fixedCostCycle: $data->cycleType
        );

        $this->validateCategory($userId, $data->categoryId, $data->categoryType);
    }

    /**
     * @throws BusinessRuleException
     */
    public function validateCategory(int $userId, int $categoryId, string $categoryType): void
    {
        if ($categoryType === SystemCategory::class) {
            if (! SystemCategory::query()->whereKey($categoryId)->exists()) {
                throw new BusinessRuleException('Invalid system category.');
            }

            return;
        }

        if ($categoryType === CustomCategory::class) {
            $exists = CustomCategory::query()
                ->whereKey($categoryId)
                ->where('user_id', $userId)
                ->exists();

            if (! $exists) {
                throw new BusinessRuleException('Invalid custom category.');
            }

            return;
        }

        throw new BusinessRuleException('Invalid category type.');
    }
}
