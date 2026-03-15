<?php

namespace App\Actions;

use App\DTOs\Onboarding\StoreOnboardingUserDTO;
use App\Enums\CycleType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessOnboardingAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, StoreOnboardingUserDTO $dto): void
    {
        DB::transaction(function () use ($user, $dto) {
            $user->update([
                'cycle_type' => $dto->budgetCycle,
                'balance' => $dto->allowanceAmount,
                'cycle_start' => \Illuminate\Support\now(),
                'has_onboarded' => true,
            ]);

            $user->fixedCosts()->delete();

            $user->fixedCosts()->createMany(
                array_map(
                    fn ($item) => [
                        'name' => $item->name,
                        'amount' => $item->amount,
                        'deduction_type' => $item->deduction_type,
                        'cycle' => $item->cycle->value,
                    ],
                    $dto->fixedCosts,
                )
            );
        });

        // $limitHarian = ($allowance - $totalFixedCost) / $cycle->countDays();
        //
        // // Hitung Saldo Utama (Uang Saku - pengeluaran status 'In')
        // $saldoUtama = $allowance - $totalInDeduction;
        //
        // return [
        //     'limit_harian' => $limitHarian,
        //     'saldo_utama' => $saldoUtama,
        //     'siklus_teks' => $cycle->value,
        //     'rekomendasi' => $rekomendasi ?? [],
        // ];
    }
}
