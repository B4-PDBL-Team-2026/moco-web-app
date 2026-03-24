<?php

namespace App\Domains\FixedCosts\Actions;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\DTOs\CreateFixedCostTemplateData;
use App\Domains\FixedCosts\Services\FixedCostCycleValidator;
use App\Models\CustomCategory;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\UserBudgetSetting;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Persists one or multiple fixed cost templates into the database.
 * This action acts as the final gatekeeper, ensuring that all templates
 * are logically sound, compatible with the user's budget cycle, and linked
 * to valid categories before saving them in a single database transaction.
 */
final readonly class BulkCreateFixedCostTemplateAction
{
    public function __construct(
        private FixedCostCycleValidator $fixedCostCycleValidator,
    ) {}

    /**
     * Execute the template creation process.
     *
     * @param  int  $userId  The owner of the fixed costs.
     * @param  list<CreateFixedCostTemplateData>  $fixedCosts  Array of validated DTOs.
     *
     * @throws ModelNotFoundException If the user has not completed onboarding (missing budget settings).
     * @throws InvalidArgumentException If internal validation rules are violated.
     * @throws Throwable If the database transaction fails.
     */
    public function execute(int $userId, array $fixedCosts): void
    {
        $userBudgetCycle = UserBudgetSetting::query()
            ->where('user_id', '=', $userId)
            ->firstOrFail(['cycle_type'])
            ->cycle_type;

        DB::transaction(function () use ($userId, $fixedCosts, $userBudgetCycle): void {
            foreach ($fixedCosts as $fixedCost) {
                $this->validate($userId, $fixedCost, $userBudgetCycle);

                FixedCostTemplate::query()->create([
                    'user_id' => $userId,
                    'name' => $fixedCost->name,
                    'amount' => $fixedCost->amount,
                    'cycle_type' => $fixedCost->cycleType->value,
                    'due_day' => $fixedCost->dueDay,
                    'is_active' => $fixedCost->isActive,
                    'category_type' => $fixedCost->categoryType,
                    'category_id' => $fixedCost->categoryId,
                ]);
            }
        });
    }

    private function validate(int $userId, CreateFixedCostTemplateData $fixedCost, CycleType $userBudgetCycle): void
    {
        if (trim($fixedCost->name) === '') {
            throw new InvalidArgumentException('Fixed cost name is required.');
        }

        if ((float) $fixedCost->amount <= 0) {
            throw new InvalidArgumentException('Fixed cost amount must be greater than zero.');
        }

        if ($fixedCost->cycleType === CycleType::WEEKLY && ($fixedCost->dueDay < 1 || $fixedCost->dueDay > 7)) {
            throw new InvalidArgumentException('Weekly due day must be between 1 and 7.');
        }

        if ($fixedCost->cycleType === CycleType::MONTHLY && ($fixedCost->dueDay < 1 || $fixedCost->dueDay > 31)) {
            throw new InvalidArgumentException('Monthly due day must be between 1 and 31.');
        }

        $this->fixedCostCycleValidator->ensureAllowed(
            budgetCycle: $userBudgetCycle,
            fixedCostCycle: $fixedCost->cycleType
        );

        if ($fixedCost->categoryType === SystemCategory::class) {
            if (! SystemCategory::query()->whereKey($fixedCost->categoryId)->exists()) {
                throw new InvalidArgumentException('Invalid category.');
            }

            return;
        }

        if ($fixedCost->categoryType === CustomCategory::class) {
            $customCategoryExists = CustomCategory::query()
                ->whereKey($fixedCost->categoryId)
                ->where('user_id', '=', $userId)
                ->exists();

            if (! $customCategoryExists) {
                throw new InvalidArgumentException('Invalid custom category.');
            }

            return;
        }

        throw new InvalidArgumentException('Invalid category type.');
    }
}
