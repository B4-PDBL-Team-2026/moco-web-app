<?php

namespace App\Domains\Budgeting\Actions;

use App\Domains\Budgeting\DTOs\CompleteOnboardingData;
use App\Domains\Budgeting\Enums\DeductionType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CompleteOnboardingAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, CompleteOnboardingData $dto): void
    {
        DB::transaction(function () use ($user, $dto) {
            $totalInDeduction = collect($dto->fixedCosts)
                ->filter(fn ($fixedCost) => $fixedCost->deductionType === DeductionType::IN)
                ->sum(fn ($fixedCost) => $fixedCost->amount);

            if ($totalInDeduction > $dto->allowanceAmount) {
                throw ValidationException::withMessages([
                    'fixedCosts' => 'Total of fixed costs exceeds allowance.',
                ]);
            }

            $finalBalance = $dto->allowanceAmount - $totalInDeduction;

            $user->update([
                'cycle_type' => $dto->budgetCycle->value,
                'allowance_amount' => $dto->allowanceAmount,
                'balance' => $finalBalance,
                'cycle_start' => now(),
                'has_onboarded' => true,
            ]);

            $user->fixedCosts()->delete();

            $user->fixedCosts()->createMany(
                array_map(
                    fn ($item) => [
                        'name' => $item->name,
                        'amount' => $item->amount,
                        'deduction_type' => $item->deductionType->value,
                        'cycle' => $item->cycle->value,
                    ],
                    $dto->fixedCosts,
                )
            );
        });
    }
}
