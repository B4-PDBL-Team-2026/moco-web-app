<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use App\Enums\TransactionType;


class DailyAllowanceCalculator
{
    /**
     * Recalculate the daily allowance for a user.
     *
     * @param User $user
     * @param string $newBalance
     * @return string new daily allowance
     */
    public function recalculate(User $user, string $newBalance): string
    {
        // Tentukan cycle (misalnya monthly)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        // Hitung jumlah hari tersisa dalam cycle
        $daysRemaining = Carbon::now()->diffInDays($endOfMonth) + 1;

        // Ambil leftover allowance dari cycle sebelumnya (rule 20)
        $leftover = $user->leftover_allowance ?? '0';

        // Daily allowance baru = (balance + leftover) / daysRemaining
        $dailyAllowance = bcdiv(
            bcadd($newBalance, $leftover, 2),
            (string) $daysRemaining,
            2
        );

        // Update user record
        $user->update([
            'daily_allowance' => $dailyAllowance,
        ]);

        return $dailyAllowance;
    }

    /**
     * Calculate leftover allowance
     */
    private function calculateLeftover(User $user, string $dailyAllowance): string
    {
        // Misalnya leftover = allowance yang tidak terpakai kemarin
        $todayExpense = $user->transactions()
            ->whereDate('transaction_date', Carbon::today())
            ->where('type', TransactionType::EXPENSE->value)
            ->sum('amount');

        $leftover = bcsub($dailyAllowance, (string) $todayExpense, 2);

        $user->update([
            'leftover_allowance' => $leftover,
        ]);

        return $leftover;
    }
}
