<?php

namespace App\Domains\Budgeting\Actions;

use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\FixedCostOccurrence;
use App\Models\Transaction;
use App\Models\UserBudgetSnapshot;
use App\Models\UserBudgetSetting;
use App\Models\User;
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
        $userNow = $now->setTimezone($setting->timezone);
        $today   = $userNow->toDateString();

        // today_spent: sum of expense transactions with transaction_date = today
        $todaySpent = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::EXPENSE->value)
            ->whereDate('transaction_date', $today)
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
                'name'      => $occurrence->name,
                'amount'    => (int) $occurrence->amount,
                'cycle'     => $occurrence->cycle_type->value,
                'due_value' => $occurrence->due_date->day,
            ])
            ->values()
            ->toArray();

        return [
            'server_time'               => $now->toIso8601String(),
            'current_balance'           => (int) $snapshot->current_balance,
            'budget_cycle'              => $setting->cycle_type->value,
            'safety_ceiling'            => (int) $setting->ceiling_limit,
            'safety_flooring'           => (int) $setting->flooring_limit,
            'today_spent'               => (int) $todaySpent,
            'today_limit'               => (int) $snapshot->daily_allowance_limit,
            'tomorrow_limit_prediction' => (int) $snapshot->remaining_daily_allowance,
            'raw_today_limit'           => (int) $snapshot->raw_daily_allowance,
            'unpaid_fixed_costs'        => $unpaidFixedCosts,
        ];
    }
}