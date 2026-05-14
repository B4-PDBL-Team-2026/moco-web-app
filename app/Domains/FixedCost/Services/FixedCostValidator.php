<?php

namespace App\Domains\FixedCost\Services;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\ValueObjects\Money;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\DTOs\CreateFixedCostTemplateData;
use App\Domains\FixedCost\DTOs\UpdateFixedCostTemplateData;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;

final readonly class FixedCostValidator
{
    /**
     * @throws BusinessRuleException
     */
    public function validateCreate(int $userId, CreateFixedCostTemplateData $data): void
    {
        $user = User::with(['budgetSetting'])->findOrFail($userId);

        $this->ensureValidName($data->name);
        $this->ensureValidAmount($data->amount);
        $this->ensureValidDueDay($data->cycleType, $data->dueDay);

        $this->validateCycleCompatibility(
            budgetCycle: $user->budgetSetting->cycle_type,
            fixedCostCycle: $data->cycleType
        );

        $this->validateCategory($userId, $data->categoryId);
    }

    /**
     * @throws BusinessRuleException
     */
    public function validateUpdate(int $userId, FixedCostTemplate $template, UpdateFixedCostTemplateData $data): void
    {
        $user = User::with(['budgetSetting'])->findOrFail($userId);

        $effectiveName = $data->name ?? $template->name;
        $effectiveAmount = $data->amount ?? (string) $template->amount;

        $effectiveCycleType = $data->cycleType ?? CycleType::from($template->cycle_type->value);
        $effectiveDueDay = $data->dueDay ?? (int) $template->due_day;

        $effectiveCategoryId = $data->categoryId ?? $template->category_id;

        $this->ensureValidName($effectiveName);
        $this->ensureValidAmount($effectiveAmount);
        $this->ensureValidDueDay($effectiveCycleType, $effectiveDueDay);

        $this->validateCycleCompatibility(
            budgetCycle: $user->budgetSetting->cycle_type,
            fixedCostCycle: $effectiveCycleType
        );

        if ($data->categoryId !== null) {
            $this->validateCategory($userId, $effectiveCategoryId);
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function ensureValidName(?string $name): void
    {
        if (trim((string) $name) === '') {
            throw new BusinessRuleException('errors.fixed_cost.name_empty');
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function ensureValidAmount(?string $amount): void
    {
        if ($amount !== null && Money::lte($amount, '0')) {
            throw new BusinessRuleException('errors.fixed_cost.amount_invalid');
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function ensureValidDueDay(CycleType $cycleType, int $dueDay): void
    {
        if ($cycleType === CycleType::WEEKLY && ($dueDay < 1 || $dueDay > 7)) {
            throw new BusinessRuleException('errors.fixed_cost.due_day_weekly_invalid');
        }

        if ($cycleType === CycleType::MONTHLY && ($dueDay < 1 || $dueDay > 31)) {
            throw new BusinessRuleException('errors.fixed_cost.due_day_monthly_invalid');
        }
    }

    /**
     * @throws BusinessRuleException
     */
    public function validateCycleCompatibility(CycleType $budgetCycle, CycleType $fixedCostCycle): void
    {
        if ($budgetCycle === CycleType::WEEKLY && $fixedCostCycle === CycleType::MONTHLY) {
            throw new BusinessRuleException('errors.fixed_cost.cycle_mismatch');
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function validateCategory(int $userId, int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        if (! $category->is_system && $category->user_id !== $userId) {
            throw new BusinessRuleException('errors.category.invalid_ownership');
        }

        if ($category->type !== TransactionType::EXPENSE) {
            throw new BusinessRuleException('errors.category.mismatch');
        }
    }
}
