<?php

namespace App\Domains\Budgeting\Actions;

use App\Domains\Budgeting\DTOs\CompleteOnboardingData;
use App\Domains\Budgeting\DTOs\OnboardingResultData;
use App\Domains\FixedCosts\Actions\CreateFixedCostTemplateAction;
use App\Domains\FixedCosts\Services\FixedCostCycleValidator;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

final readonly class CompleteOnboardingAction
{
    public function __construct(
        private FixedCostCycleValidator $validateFixedCostCycleCompability,
        private RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
        private CreateFixedCostTemplateAction $createFixedCostTemplatesAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(int $userId, CompleteOnboardingData $data): OnboardingResultData
    {
        $this->validate($data);

        return DB::transaction(function () use ($userId, $data) {
            $user = User::query()
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            UserBudgetSetting::query()->updateOrCreate(
                ['user_id' => $userId],
                [
                    'cycle_type' => $data->cycleType->value,
                    'initial_balance' => $data->initialBalance,
                    'flooring_limit' => $data->flooringLimit,
                    'ceiling_limit' => $data->ceilingLimit,
                    'timezone' => $data->timezone,
                ]
            );

            $this->createInitialBalanceTransaction($userId, $data);

            foreach ($data->fixedCosts as $fixedCost) {
                $this->validateFixedCostCycleCompability->ensureAllowed(
                    budgetCycle: $data->cycleType,
                    fixedCostCycle: $fixedCost->cycleType,
                );
            }

            if ($data->hasFixedCosts()) {
                $this->createFixedCostTemplatesAction->execute($userId, $data->fixedCosts);
            }

            $snapshot = $this->recalculateBudgetSnapshotAction->execute($userId);

            $user->forceFill([
                'has_onboarded' => true,
            ])->save();

            return new OnboardingResultData(
                userId: $user->id,
                cycleType: $data->cycleType->value,
                currentBalance: (string) $snapshot->current_balance,
                reservedCost: (string) $snapshot->reserved_cost,
                dailyAllowance: (string) $snapshot->daily_allowance,
                cycleKey: $snapshot->current_cycle_key,
                cycleStartDate: $snapshot->cycle_start_date->toDateString(),
                cycleEndDate: $snapshot->cycle_end_date->toDateString(),
                remainingDays: (int) $snapshot->remaining_days,
                fixedCostsCount: count($data->fixedCosts),
                hasOnboarded: true,
            );
        });
    }

    private function validate(CompleteOnboardingData $data): void
    {
        if ((float) $data->initialBalance <= 0) {
            throw new InvalidArgumentException('Initial balance must be greater than 0.');
        }

        if ((float) $data->flooringLimit < 0) {
            throw new InvalidArgumentException('Flooring limit cannot be negative.');
        }

        if ((float) $data->ceilingLimit < (float) $data->flooringLimit) {
            throw new InvalidArgumentException('Ceiling limit must be greater than or equal to flooring limit.');
        }
    }

    private function createInitialBalanceTransaction(
        int $userId,
        CompleteOnboardingData $data,
    ): void {
        $exists = Transaction::query()
            ->where('user_id', $userId)
            ->where('source', TransactionSource::INITIAL_BALANCE->value)
            ->exists();

        if ($exists) {
            return;
        }

        $initialAllowanceCategory = SystemCategory::query()->where('name', '=', 'Uang saku')->value('id');

        Transaction::query()->create([
            'user_id' => $userId,
            'type' => TransactionType::INCOME->value,
            'source' => TransactionSource::INITIAL_BALANCE->value,
            'name' => 'initial balance',
            'amount' => $data->initialBalance,
            'transaction_date' => now($data->timezone)->toDateString(),
            'category_id' => $initialAllowanceCategory,
            'category_type' => SystemCategory::class,
        ]);
    }
}
