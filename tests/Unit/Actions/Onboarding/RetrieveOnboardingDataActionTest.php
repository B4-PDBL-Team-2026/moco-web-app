<?php

declare(strict_types=1);

use App\Actions\Onboarding\RetrieveOnboardingDataAction;
use App\Enums\CycleType;
use App\Enums\DeductionType;
use App\Models\FixedCost;
use App\Models\User;

it('returns onboarding form data and summary', function (): void {
    $user = User::factory()->create([
        'cycle_type' => CycleType::MONTHLY->value,
        'allowance_amount' => 3_000_000,
        'balance' => 2_000_000,
    ]);

    FixedCost::query()->create([
        'user_id' => $user->id,
        'name' => 'Rent',
        'amount' => 1_000_000,
        'deduction_type' => DeductionType::IN->value,
        'cycle' => CycleType::MONTHLY->value,
    ]);

    FixedCost::query()->create([
        'user_id' => $user->id,
        'name' => 'Internet',
        'amount' => 300_000,
        'deduction_type' => DeductionType::IN->value,
        'cycle' => CycleType::MONTHLY->value,
    ]);

    $result = app(RetrieveOnboardingDataAction::class)->execute($user->fresh());

    expect($result)->toHaveKeys(['form', 'summary'])
        ->and($result['form']['budgetCycle'])->toBe(CycleType::MONTHLY->value)
        ->and((float) $result['form']['allowanceAmount'])->toBe(3_000_000.0)
        ->and($result['form']['fixedCosts'])->toHaveCount(2)
        ->and((float) $result['summary']['balance'])->toBe(2_000_000.0)
        ->and((float) $result['summary']['totalFixedCosts'])->toBe(1_300_000.0)
        ->and((float) $result['summary']['dailyLimit'])
        ->toEqualWithDelta(2_000_000 / CycleType::MONTHLY->countDays(), 0.0001)
        ->and($result['form']['fixedCosts'][0])->toHaveKeys([
            'id',
            'name',
            'amount',
            'deductionType',
            'cycle',
        ]);

});

it('returns empty fixed costs and zero total when user has no fixed costs', function (): void {
    $user = User::factory()->create([
        'cycle_type' => CycleType::WEEKLY->value,
        'allowance_amount' => 700_000,
        'balance' => 500_000,
    ]);

    $result = app(RetrieveOnboardingDataAction::class)->execute($user->fresh());

    expect($result['form']['fixedCosts'])->toBe([])
        ->and((float) $result['summary']['totalFixedCosts'])->toBe(0.0)
        ->and((float) $result['summary']['dailyLimit'])
        ->toEqualWithDelta(500_000 / CycleType::WEEKLY->countDays(), 0.0001);
});

it('maps each fixed cost in api-friendly shape', function (): void {
    $user = User::factory()->create([
        'cycle_type' => CycleType::MONTHLY->value,
        'allowance_amount' => 1_500_000,
        'balance' => 1_000_000,
    ]);

    $fixedCost = FixedCost::query()->create([
        'user_id' => $user->id,
        'name' => 'Spotify',
        'amount' => 99_999,
        'deduction_type' => DeductionType::IN->value,
        'cycle' => CycleType::MONTHLY->value,
    ]);

    $result = app(RetrieveOnboardingDataAction::class)->execute($user->fresh());

    expect($result['form']['fixedCosts'])->toBe([
        [
            'id' => $fixedCost->id,
            'name' => 'Spotify',
            'amount' => 99999.0,
            'deductionType' => DeductionType::IN->value,
            'cycle' => CycleType::MONTHLY->value,
        ],
    ]);
});

it('returns zero daily limit when cycle type is missing', function (): void {
    $user = User::factory()->create([
        'cycle_type' => null,
        'allowance_amount' => 900_000,
        'balance' => 600_000,
    ]);

    $result = app(RetrieveOnboardingDataAction::class)->execute($user->fresh());

    expect($result['form']['budgetCycle'])->toBeNull()
        ->and((float) $result['summary']['dailyLimit'])->toBe(0.0);
});
