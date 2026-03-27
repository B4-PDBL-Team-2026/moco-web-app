<?php

namespace App\Domains\Transactions\Actions;

use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\FixedCostOccurrence;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use Carbon\CarbonImmutable;

class GetDashboardSummaryAction
{
    public function execute(User $user): array
    {
        $snapshot = $user->budgetSnapshot;
        $settings = UserBudgetSetting::where('user_id', $user->id)->first();

        // today spent
        $todaySpent = (string) Transaction::query()
            ->where('user_id', $user->id)
            ->whereDate('transaction_date', now()->toDateString())
            ->where('type', 'expense')
            ->sum('amount');

        // unpaid fixed costs
        $unpaidFixedCosts = FixedCostOccurrence::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                FixedCostOccurenceStatus::PENDING,
                FixedCostOccurenceStatus::OVERDUE,
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'amount' => (string) $item->amount,
                    'cycle' => $item->cycle,
                    'due_value' => $item->due_value,
                ];
            });

        return [
            'server_time' => CarbonImmutable::now()->toISOString(),
            'current_balance' => (string) $snapshot->current_balance,
            'budget_cycle' => $settings->cycle_type,
            'safety_ceiling' => (string) $settings->ceiling_limit,
            'safety_flooring' => (string) $settings->flooring_limit,
            'today_spent' => $todaySpent,

            // mapping 
            'today_limit' => (string) $snapshot->daily_allowance_limit,
            'tomorrow_limit_prediction' => (string) $snapshot->remaining_daily_allowance,
            'raw_today_limit' => (string) $snapshot->raw_daily_allowance,

            'unpaid_fixed_costs' => $unpaidFixedCosts,
        ];
    }
}