<?php

namespace App\Domains\Budgeting\Actions;

use App\Commons\MoneyService;
use App\Domains\Budgeting\Services\AllowanceCalculatorService;
use App\Domains\Budgeting\Services\CycleResolverService;
use App\Domains\Budgeting\Services\ReservedCostCalculatorService;
use App\Domains\FixedCosts\Actions\GenerateCurrentCycleOccurrencesAction;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class RecalculateBudgetSnapshotAction
{
    public function __construct(
        private CycleResolverService $cycleResolverService,
        private AllowanceCalculatorService $allowanceCalculatorService,
        private ReservedCostCalculatorService $reservedCostCalculatorService,
        private GenerateCurrentCycleOccurrencesAction $generateCurrentCycleOccurrencesAction,
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

            $this->generateCurrentCycleOccurrencesAction->execute(
                userId: $userId,
                now: $now,
                timezone: $settings->timezone ?? 'Asia/Jakarta',
            );

            $cycle = $this->cycleResolverService->resolve(
                cycleType: $settings->cycle_type,
                now: $now,
                timezone: $settings->timezone ?? 'Asia/Jakarta',
            );

            $balance = $this->resolveCurrentBalance($userId);

            $reservedCost = $this->reservedCostCalculatorService->calculateForCurrentCycle(
                userId: $userId,
                cycleKey: $cycle->cycleKey,
                today: $now->startOfDay(),
            );

            $dailyAllowance = $this->allowanceCalculatorService->calculate(
                balance: $balance,
                reservedCost: $reservedCost,
                ceilingLimit: $settings->ceiling_limit ? (string) $settings->ceiling_limit : null,
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
        $balance = '0.00';

        $transactions = Transaction::query()
            ->where('user_id', $userId)
            ->get(['type', 'amount']);

        foreach ($transactions as $transaction) {
            if ($transaction->type === TransactionType::INCOME) {
                $balance = MoneyService::add($balance, (string) $transaction->amount);

                continue;
            }

            $balance = MoneyService::sub($balance, (string) $transaction->amount);
        }

        return $balance;
    }
}
