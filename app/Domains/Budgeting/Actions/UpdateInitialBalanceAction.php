<?php

namespace App\Domains\Budgeting\Actions;

use App\Models\User;

class UpdateInitialBalanceAction
{
    public function execute(User $user): array
    {
        $fixedCosts = $user->fixedCosts()->get();

        $mappedFixedCosts = $fixedCosts->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'amount' => (float) $item->amount,
            'deductionType' => $item->deduction_type->value,
            'cycle' => $item->cycle->value,
        ])->values()->all();

        $allowanceAmount = (float) $user->allowance_amount;

        $cycleType = $user->cycle_type;

        $balance = (float) $user->balance;

        $totalFixedCosts = $fixedCosts->sum(fn ($fixedCost) => $fixedCost->amount);

        $cycleDays = $cycleType?->countDays() ?? 0;

        $dailyLimit = $cycleDays > 0
            ? $balance / $cycleDays
            : 0.0;

        return [
            'form' => [
                'budgetCycle' => $cycleType?->value,
                'allowanceAmount' => $allowanceAmount,
                'fixedCosts' => $mappedFixedCosts,
            ],
            'summary' => [
                'balance' => $balance,
                'totalFixedCosts' => $totalFixedCosts,
                'dailyLimit' => $dailyLimit,
            ],
        ];
    }
}
