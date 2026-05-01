<?php

namespace App\Domains\Budgeting\Actions;

use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Budgeting\Services\BudgetCycleWindowCalculator;
use App\Domains\Budgeting\Services\DailyAllowanceCalculator;
use App\Domains\Budgeting\Services\ReservedCostCalculator;
use App\Domains\FixedCost\Actions\GenerateOccurencesForBudgetWindowAction;
use App\Domains\Transaction\Services\UserBalanceCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecalculateBudgetSnapshotAction
{
    public function __construct(
        private readonly UserBalanceCalculator $userBalanceCalculator,
        private readonly BudgetCycleWindowCalculator $cycleResolverService,
        private readonly DailyAllowanceCalculator $allowanceCalculatorService,
        private readonly ReservedCostCalculator $reservedCostCalculatorService,
        private readonly GenerateOccurencesForBudgetWindowAction $generateCurrentCycleOccurrencesAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(
        int $userId,
        ?CarbonImmutable $now = null,
        bool $forceUpdateLimit = false,
    ): UserBudgetSnapshot {
        $now ??= CarbonImmutable::now('Asia/Jakarta');

        return DB::transaction(function () use ($userId, $now, $forceUpdateLimit) {
            $settings = UserBudgetSetting::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $timezone = $settings->timezone ?? 'Asia/Jakarta';

            $now = $now
                ? $now->setTimezone($timezone)
                : CarbonImmutable::now($timezone);

            $cycle = $this->cycleResolverService->calculateFor(
                cycleType: $settings->cycle_type,
                now: $now,
                timezone: $timezone,
            );

            $this->generateCurrentCycleOccurrencesAction->execute(
                userId: $userId,
                budgetStartDate: $cycle->startDate,
                budgetEndDate: $cycle->endDate,
                now: $now,
                timezone: $timezone,
            );

            $balance = $this->userBalanceCalculator->calculateCurrentBalance($userId);

            $reservedCost = $this->reservedCostCalculatorService->calculateForBudgetWindow(
                userId: $userId,
                budgetWindowStart: $cycle->startDate,
                budgetWindowEnd: $cycle->endDate,
            );

            $dailyAllowance = $this->allowanceCalculatorService->calculate(
                balance: $balance,
                reservedCost: $reservedCost,
                ceilingLimit: (string) $settings->ceiling_limit,
                flooringLimit: (string) $settings->flooring_limit,
                remainingDays: $cycle->remainingDays,
            );

            $existingSnapshot = UserBudgetSnapshot::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            $isNewDay = true;

            if ($existingSnapshot && $existingSnapshot->recalculated_at) {
                $lastRecalculated = CarbonImmutable::parse($existingSnapshot->recalculated_at)
                    ->setTimezone($timezone)
                    ->startOfDay();

                $isNewDay = $now->startOfDay()->greaterThan($lastRecalculated);
            }

            $payload = [
                'current_balance' => $balance,
                'reserved_cost' => $reservedCost,
                'remaining_daily_allowance' => $dailyAllowance->amount,
                'raw_daily_allowance' => $dailyAllowance->rawAmount,
                'current_cycle_key' => $cycle->cycleKey,
                'cycle_start_date' => $cycle->startDate->toDateString(),
                'cycle_end_date' => $cycle->endDate->toDateString(),
                'remaining_days' => $cycle->remainingDays,
                'recalculated_at' => $now,
            ];

            if ($isNewDay || $forceUpdateLimit) {
                $payload['daily_allowance_limit'] = $dailyAllowance->amount;
            }

            if ($existingSnapshot) {
                $existingSnapshot->update($payload);

                return $existingSnapshot->refresh();
            }

            return UserBudgetSnapshot::query()->create(array_merge(['user_id' => $userId], $payload));
        });
    }
}
