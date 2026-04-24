<?php

namespace App\Domains\FixedCosts\Actions;

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\FixedCosts\DTOs\CreateFixedCostTemplateData;
use App\Domains\FixedCosts\Services\FixedCostValidator;
use App\Models\FixedCostTemplate;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Creates a single fixed cost template during normal (post-onboarding) usage.
 *
 * Differences from CreateFixedCostTemplateAction (the onboarding batch action):
 * - Accepts one template at a time (not an array).
 * - After persisting the template, immediately generates the occurrence for the
 *   current budget window so the user sees it right away.
 * - Triggers a budget snapshot recalculation because the new template may push
 *   reserved cost above balance, which collapses daily allowance to the
 *   flooring limit (BR §12).
 *
 * Validation rules are identical to the onboarding action — the same
 * FixedCostCycleValidator is used to enforce cycle compatibility.
 */
class CreateFixedCostTemplateAction
{
    public function __construct(
        private readonly FixedCostValidator $fixedCostValidator,
        private readonly GenerateOccurencesForBudgetWindowAction $generateOccurrences,
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshot,
    ) {}

    /**
     * @throws ModelNotFoundException If the user has not completed onboarding.
     * @throws InvalidArgumentException If validation rules are violated.
     * @throws Throwable If the DB transaction fails.
     */
    public function execute(int $userId, CreateFixedCostTemplateData $fixedCost): FixedCostTemplate
    {
        $budgetSetting = UserBudgetSetting::query()
            ->where('user_id', $userId)
            ->firstOrFail(['cycle_type', 'timezone']);

        $this->fixedCostValidator->validateFixedCostData($userId, $fixedCost, $budgetSetting->cycle_type);

        return DB::transaction(function () use ($userId, $fixedCost, $budgetSetting): FixedCostTemplate {
            $template = FixedCostTemplate::query()->create([
                'user_id' => $userId,
                'name' => $fixedCost->name,
                'amount' => $fixedCost->amount,
                'cycle_type' => $fixedCost->cycleType->value,
                'due_day' => $fixedCost->dueDay,
                'is_active' => $fixedCost->isActive,
                'category_type' => $fixedCost->categoryType,
                'category_id' => $fixedCost->categoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Generate the occurrence for the current window immediately so the
            // user can see (and act on) the new bill without waiting for a scheduled job.
            $snapshot = UserBudgetSnapshot::query()
                ->where('user_id', $userId)
                ->firstOrFail(['cycle_start_date', 'cycle_end_date']);

            $this->generateOccurrences->execute(
                userId: $userId,
                budgetStartDate: CarbonImmutable::parse($snapshot->cycle_start_date, $budgetSetting->timezone),
                budgetEndDate: CarbonImmutable::parse($snapshot->cycle_end_date, $budgetSetting->timezone),
                timezone: $budgetSetting->timezone,
            );

            // recalculate snapshot — if reserved cost now exceeds balance,
            // daily allowance collapses to flooring limit automatically.
            $this->recalculateBudgetSnapshot->execute($userId);

            return $template->refresh();
        });
    }
}
