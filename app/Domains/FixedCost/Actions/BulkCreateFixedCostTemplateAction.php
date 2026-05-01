<?php

namespace App\Domains\FixedCost\Actions;

use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\FixedCost\DTOs\CreateFixedCostTemplateData;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\FixedCost\Services\FixedCostValidator;
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
        private FixedCostValidator $fixedCostValidator,
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
                $this->fixedCostValidator->validateCreate($userId, $fixedCost, $userBudgetCycle);

                FixedCostTemplate::query()->create([
                    'user_id' => $userId,
                    'name' => $fixedCost->name,
                    'amount' => $fixedCost->amount,
                    'cycle_type' => $fixedCost->cycleType->value,
                    'due_day' => $fixedCost->dueDay,
                    'is_active' => $fixedCost->isActive,
                    'category_id' => $fixedCost->categoryId,
                ]);
            }
        });
    }
}
