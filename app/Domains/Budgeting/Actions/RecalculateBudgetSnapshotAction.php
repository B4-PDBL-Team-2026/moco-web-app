<?php

namespace App\Domains\Budgeting\Actions;

use App\Commons\MoneyService;
use App\Domains\Budgeting\Services\DailyAllowanceCalculator;
use App\Domains\Budgeting\Services\BudgetCycleWindowCalculator;
use App\Domains\Budgeting\Services\ReservedCostCalculator;
use App\Domains\FixedCosts\Actions\GenerateOccurencesForBudgetWindowAction;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecalculateBudgetSnapshotAction
{
    public function __construct(
        private readonly BudgetCycleWindowCalculator             $cycleResolverService,
        private readonly DailyAllowanceCalculator                $allowanceCalculatorService,
        private readonly ReservedCostCalculator                  $reservedCostCalculatorService,
        private readonly GenerateOccurencesForBudgetWindowAction $generateCurrentCycleOccurrencesAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(int $userId, ?CarbonImmutable $now = null): UserBudgetSnapshot
    {
        $now ??= CarbonImmutable::now('Asia/Jakarta');

        return DB::transaction(function () use ($userId, $now) {
            $settings = UserBudgetSetting::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $cycle = $this->cycleResolverService->calculateFor(
                cycleType: $settings->cycle_type,
                now: $now,
                timezone: $settings->timezone ?? 'Asia/Jakarta',
            );

            $this->generateCurrentCycleOccurrencesAction->execute(
                userId: $userId,
                budgetStartDate: $cycle->startDate,
                budgetEndDate: $cycle->endDate,
                now: $now,
                timezone: $settings->timezone ?? 'Asia/Jakarta',
            );

            $balance = $this->resolveCurrentBalance($userId);

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

            return UserBudgetSnapshot::query()->updateOrCreate(
                ['user_id' => $userId],
                [
                    'current_balance' => $balance,
                    'reserved_cost' => $reservedCost,
                    'daily_allowance' => $dailyAllowance->amount,
                    'actual_daily_allowance' => $dailyAllowance->actualAmount,
                    'current_cycle_key' => $cycle->cycleKey,
                    'cycle_start_date' => $cycle->startDate->toDateString(),
                    'cycle_end_date' => $cycle->endDate->toDateString(),
                    'remaining_days' => $cycle->remainingDays,
                    'recalculated_at' => $now,
                ]
            );
        });
    }

    /**
     * Calculate current balance based on transaction list.
     */
    private function resolveCurrentBalance(int $userId): string
    {
        $totalIncome = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionType::INCOME)
            ->sum('amount') ?? 0;

        $totalExpense = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionType::EXPENSE)
            ->sum('amount') ?? 0;

        return MoneyService::sub((string) $totalIncome, (string) $totalExpense);
    }
}
