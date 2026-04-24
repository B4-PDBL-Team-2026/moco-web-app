<?php

namespace App\Domains\FixedCosts\Services;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\Services\MoneyService;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\DTOs\CreateFixedCostTemplateData;
use App\Domains\FixedCosts\DTOs\UpdateFixedCostTemplateData;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Category;
use App\Models\FixedCostTemplate;
use App\Models\User;

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
            throw new BusinessRuleException('Fixed cost name cannot be empty.');
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function ensureValidAmount(?string $amount): void
    {
        if ($amount !== null && MoneyService::lte($amount, '0')) {
            throw new BusinessRuleException('Fixed cost amount must be greater than zero.');
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function ensureValidDueDay(CycleType $cycleType, int $dueDay): void
    {
        if ($cycleType === CycleType::WEEKLY && ($dueDay < 1 || $dueDay > 7)) {
            throw new BusinessRuleException('Weekly due day must be between 1 and 7.');
        }

        if ($cycleType === CycleType::MONTHLY && ($dueDay < 1 || $dueDay > 31)) {
            throw new BusinessRuleException('Monthly due day must be between 1 and 31.');
        }
    }

    /**
     * @throws BusinessRuleException
     */
    public function validateCycleCompatibility(CycleType $budgetCycle, CycleType $fixedCostCycle): void
    {
        if ($budgetCycle === CycleType::WEEKLY && $fixedCostCycle === CycleType::MONTHLY) {
            throw new BusinessRuleException('Monthly fixed cost is not allowed when budget cycle is weekly.');
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function validateCategory(int $userId, int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        if (! $category->is_system && $category->user_id !== $userId) {
            throw new BusinessRuleException('Invalid category ownership.');
        }

        if ($category->type !== TransactionType::EXPENSE) {
            throw new BusinessRuleException('Category must be an expense type.');
        }
    }
}
