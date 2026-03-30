<?php

namespace App\Domains\Budgeting\Actions;

use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\FixedCostOccurrence;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;

class GetDashboardSummaryAction
{
    /**
     * Retrieve all data needed for the dashboard response.
     */
    public function execute(User $user, CarbonImmutable $now): array
    {
        $snapshot = UserBudgetSnapshot::where('user_id', $user->id)
            ->firstOrFail();

        $setting = UserBudgetSetting::where('user_id', $user->id)
            ->firstOrFail();

        // Determine today's date based on user timezone
        $userTimezone = $setting->timezone ?? 'UTC';
        $userNow = $now->setTimezone($userTimezone);

        $startOfDayUser = $userNow->startOfDay();
        $endOfDayUser = $userNow->endOfDay();

        $startOfDayUTC = $startOfDayUser->setTimezone('UTC');
        $endOfDayUTC = $endOfDayUser->setTimezone('UTC');

        // today_spent: sum of expense transactions with transaction_date = today
        $todaySpent = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::EXPENSE->value)
            ->where('source', '!=', TransactionSource::FIXED_COST_PAYMENT->value)
            ->whereBetween('transaction_date', [
                $startOfDayUTC->toDateTimeString(),
                $endOfDayUTC->toDateTimeString(),
            ])
            ->whereNull('deleted_at')
            ->sum('amount');

        // unpaid fixed costs: PENDING + OVERDUE in current cycle
        $unpaidOccurrences = FixedCostOccurrence::query()
            ->where('user_id', $user->id)
            ->where('cycle_key', $snapshot->current_cycle_key)
            ->whereIn('status', [
                FixedCostOccurenceStatus::PENDING->value,
                FixedCostOccurenceStatus::OVERDUE->value,
            ])
            ->orderBy('due_date')
            ->get();

        $unpaidFixedCosts = $unpaidOccurrences
            ->map(fn (FixedCostOccurrence $occurrence) => [
                'name' => $occurrence->name,
                'amount' => (int) $occurrence->amount,
                'cycle' => $occurrence->cycle_type->value,
                'due_value' => $occurrence->due_date->day,
            ])
            ->values()
            ->toArray();

        return [
            'serverTime' => $now->toIso8601String(),
            'currentBalance' => (int) $snapshot->current_balance,
            'budgetCycle' => $setting->cycle_type->value ?? 'monthly',
            'safetyCeiling' => (int) $setting->ceiling_limit,
            'safetyFlooring' => (int) $setting->flooring_limit,
            'todaySpent' => (int) $todaySpent,
            'todayLimit' => (int) $snapshot->daily_allowance_limit,
            'tomorrowLimitPrediction' => (int) $snapshot->remaining_daily_allowance,
            'rawTodayLimit' => (int) $snapshot->raw_daily_allowance,
            'unpaidFixedCosts' => $unpaidFixedCosts,
        ];
    }
}
