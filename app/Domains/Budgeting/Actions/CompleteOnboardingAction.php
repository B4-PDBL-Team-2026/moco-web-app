<?php

namespace App\Domains\Budgeting\Actions;

use App\Domains\Budgeting\DTOs\CompleteOnboardingData;
use App\Domains\Budgeting\Enums\DeductionType;
use App\Domains\Budgeting\Services\AllowanceCalculator;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CompleteOnboardingAction
{
    public function __construct(AllowanceCalculator $calculator) {}

    /**
     * @throws Throwable
     */
    public function execute(User $user, CompleteOnboardingData $data): array
    {
        DB::transaction(function () use ($user, $data) {
            UserBudgetSetting::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'cycle_type' => $data->budgetCycle,
                    'daily_ceiling_amount' => $data->dailyCeilingAmount,
                ],
            );

            Transaction::query()->create([
                'user_id' => $user->id,
                'category_id' => $this->resolveInitialBalanceCategoryId($user),
                'name' => 'Initial Balance',
                'amount' => $data->initialBalance,
                'type' => 'income',
                'source_type' => 'initial_balance',
                'transaction_date' => now()->toDateString(),
                'effective_at' => now(),
            ]);

            $totalInDeduction = collect($data->fixedCosts)
                ->filter(fn ($fixedCost) => $fixedCost->deductionType === DeductionType::IN)
                ->sum(fn ($fixedCost) => $fixedCost->amount);

            if ($totalInDeduction > $data->allowanceAmount) {
                throw ValidationException::withMessages([
                    'fixedCosts' => 'Total of fixed costs exceeds allowance.',
                ]);
            }

            $finalBalance = $data->allowanceAmount - $totalInDeduction;

            $user->update([
                'cycle_type' => $data->budgetCycle->value,
                'allowance_amount' => $data->allowanceAmount,
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
                    $data->fixedCosts,
                )
            );
        });
    }

    private function resolveInitialBalanceCategoryId(User $user): int
    {
        return $user->categories()
            ->where('type', 'income')
            ->where('name', 'Initial Balance')
            ->value('id');
    }
}
