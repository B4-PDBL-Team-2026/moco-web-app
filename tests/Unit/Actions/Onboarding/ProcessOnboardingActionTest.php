<?php

declare(strict_types=1);

use App\Actions\Onboarding\ProcessOnboardingAction;
use App\DTOs\Budget\FixedCostDTO;
use App\DTOs\Onboarding\StoreOnboardingUserDTO;
use App\Enums\CycleType;
use App\Enums\DeductionType;
use App\Models\FixedCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function nonInDeductionType(): DeductionType
{
    $nonIn = collect(DeductionType::cases())
        ->first(fn (DeductionType $case) => $case !== DeductionType::IN);

    if (! $nonIn instanceof DeductionType) {
        throw new RuntimeException('DeductionType needs at least one non-IN case for this test.');
    }

    return $nonIn;
}

it('stores onboarding data and recalculates balance from IN fixed costs only', function (): void {
    $user = User::factory()->create([
        'cycle_type' => null,
        'allowance_amount' => null,
        'balance' => 0,
        'has_onboarded' => false,
    ]);

    $dto = new StoreOnboardingUserDTO(
        budgetCycle: CycleType::MONTHLY,
        allowanceAmount: 3_000_000,
        fixedCosts: [
            new FixedCostDTO(
                name: 'Rent',
                amount: 1_000_000,
                deductionType: DeductionType::IN,
                cycle: CycleType::MONTHLY,
            ),
            new FixedCostDTO(
                name: 'Gym',
                amount: 250_000,
                deductionType: nonInDeductionType(),
                cycle: CycleType::MONTHLY,
            ),
        ],
    );

    app(ProcessOnboardingAction::class)->execute($user, $dto);

    $user->refresh();

    expect((string) $user->cycle_type->value)->toBe(CycleType::MONTHLY->value)
        ->and((float) $user->allowance_amount)->toBe(3_000_000.0)
        ->and((float) $user->balance)->toBe(2_000_000.0)
        ->and($user->has_onboarded)->toBeTrue()
        ->and($user->cycle_start)->not->toBeNull();

    $fixedCosts = $user->fixedCosts()->orderBy('name')->get();

    expect($fixedCosts)->toHaveCount(2)
        ->and($fixedCosts->pluck('name')->all())->toBe(['Gym', 'Rent'])
        ->and((float) $fixedCosts->firstWhere('name', 'Rent')->amount)->toBe(1_000_000.0)
        ->and($fixedCosts->firstWhere('name', 'Rent')->deduction_type->value)->toBe(DeductionType::IN->value)
        ->and($fixedCosts->firstWhere('name', 'Rent')->cycle->value)->toBe(CycleType::MONTHLY->value);
});

it('replaces previous fixed costs during onboarding re-process', function (): void {
    $user = User::factory()->create([
        'cycle_type' => CycleType::WEEKLY->value,
        'allowance_amount' => 500_000,
        'balance' => 300_000,
        'has_onboarded' => true,
    ]);

    FixedCost::query()->create([
        'user_id' => $user->id,
        'name' => 'Old Cost',
        'amount' => 100_000,
        'deduction_type' => DeductionType::IN->value,
        'cycle' => CycleType::MONTHLY->value,
    ]);

    $dto = new StoreOnboardingUserDTO(
        budgetCycle: CycleType::MONTHLY,
        allowanceAmount: 1_500_000,
        fixedCosts: [
            new FixedCostDTO(
                name: 'Internet',
                amount: 300_000,
                deductionType: DeductionType::IN,
                cycle: CycleType::MONTHLY,
            ),
        ],
    );

    app(ProcessOnboardingAction::class)->execute($user, $dto);

    $user->refresh();

    expect($user->fixedCosts()->count())->toBe(1)
        ->and($user->fixedCosts()->first()->name)->toBe('Internet')
        ->and(FixedCost::query()->where('name', 'Old Cost')->exists())->toBeFalse()
        ->and((float) $user->balance)->toBe(1_200_000.0);
});

it('stores full allowance as balance when there is no IN deduction', function (): void {
    $user = User::factory()->create([
        'balance' => 0,
        'has_onboarded' => false,
    ]);

    $dto = new StoreOnboardingUserDTO(
        budgetCycle: CycleType::MONTHLY,
        allowanceAmount: 2_000_000,
        fixedCosts: [
            new FixedCostDTO(
                name: 'Phone Plan',
                amount: 200_000,
                deductionType: nonInDeductionType(),
                cycle: CycleType::MONTHLY,
            ),
        ],
    );

    app(ProcessOnboardingAction::class)->execute($user, $dto);

    $user->refresh();

    expect((float) $user->balance)->toBe(2_000_000.0)
        ->and($user->fixedCosts()->count())->toBe(1);
});

it('throws validation exception when IN deductions exceed allowance and rolls back everything', function (): void {
    $user = User::factory()->create([
        'cycle_type' => CycleType::WEEKLY->value,
        'allowance_amount' => 700_000,
        'balance' => 500_000,
        'has_onboarded' => false,
    ]);

    FixedCost::query()->create([
        'user_id' => $user->id,
        'name' => 'Existing Cost',
        'amount' => 50_000,
        'deduction_type' => DeductionType::IN->value,
        'cycle' => CycleType::MONTHLY->value,
    ]);

    $dto = new StoreOnboardingUserDTO(
        budgetCycle: CycleType::MONTHLY,
        allowanceAmount: 100_000,
        fixedCosts: [
            new FixedCostDTO(
                name: 'Rent',
                amount: 150_000,
                deductionType: DeductionType::IN,
                cycle: CycleType::MONTHLY,
            ),
        ],
    );

    expect(fn () => app(ProcessOnboardingAction::class)->execute($user, $dto))
        ->toThrow(ValidationException::class);

    $user->refresh();

    expect((string) $user->cycle_type->value)->toBe(CycleType::WEEKLY->value)
        ->and((float) $user->allowance_amount)->toBe(700_000.0)
        ->and((float) $user->balance)->toBe(500_000.0)
        ->and($user->fixedCosts()->count())->toBe(1)
        ->and($user->fixedCosts()->first()->name)->toBe('Existing Cost');
});

it('handles empty fixed costs', function (): void {
    $user = User::factory()->create([
        'balance' => 0,
        'has_onboarded' => false,
    ]);

    $dto = new StoreOnboardingUserDTO(
        budgetCycle: CycleType::MONTHLY,
        allowanceAmount: 1_000_000,
        fixedCosts: [],
    );

    app(ProcessOnboardingAction::class)->execute($user, $dto);

    $user->refresh();

    expect((float) $user->balance)->toBe(1_000_000.0)
        ->and((float) $user->allowance_amount)->toBe(1_000_000.0)
        ->and($user->fixedCosts()->count())->toBe(0)
        ->and($user->has_onboarded)->toBeTrue();
});
