<?php

declare(strict_types=1);

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Budgeting\Enums\DeductionType;
use App\Models\FixedCostTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to store onboarding data', function (): void {
    $payload = [
        'budgetCycle' => CycleType::MONTHLY->value,
        'allowanceAmount' => 3_000_000,
        'fixedCosts' => [],
    ];

    $this->postJson('/api/onboarding', $payload)
        ->assertUnauthorized();
});

it('stores onboarding data successfully with IN and non-IN fixed costs', function (): void {
    $user = User::factory()->create([
        'cycle_type' => null,
        'allowance_amount' => null,
        'balance' => 0,
        'has_onboarded' => false,
    ]);

    $payload = [
        'budgetCycle' => CycleType::MONTHLY->value,
        'allowanceAmount' => 3_000_000,
        'fixedCosts' => [
            [
                'name' => 'Rent',
                'amount' => 1_000_000,
                'deductionType' => DeductionType::IN->value,
                'cycle' => CycleType::MONTHLY->value,
            ],
            [
                'name' => 'Gym',
                'amount' => 250_000,
                'deductionType' => nonInDeductionType()->value,
                'cycle' => CycleType::MONTHLY->value,
            ],
        ],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Onboarding data successfully stored.');

    $user->refresh();

    expect($user->cycle_type->value)->toBe(CycleType::MONTHLY->value)
        ->and((float) $user->allowance_amount)->toBe(3_000_000.0)
        ->and((float) $user->balance)->toBe(2_000_000.0)
        ->and($user->has_onboarded)->toBeTrue()
        ->and($user->cycle_start)->not->toBeNull();

    $fixedCosts = $user->fixedCosts()->orderBy('name')->get();

    expect($fixedCosts)->toHaveCount(2)
        ->and($fixedCosts->pluck('name')->all())->toBe(['Gym', 'Rent'])
        ->and($fixedCosts->firstWhere('name', 'Rent')->deduction_type->value)->toBe(DeductionType::IN->value)
        ->and($fixedCosts->firstWhere('name', 'Gym')->cycle->value)->toBe(CycleType::MONTHLY->value);
});

it('stores onboarding successfully when fixed costs are omitted', function (): void {
    $user = User::factory()->create([
        'balance' => 0,
        'has_onboarded' => false,
    ]);

    $payload = [
        'budgetCycle' => CycleType::MONTHLY->value,
        'allowanceAmount' => 1_500_000,
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertOk()
        ->assertJsonPath('success', true);

    $user->refresh();

    expect((float) $user->allowance_amount)->toBe(1_500_000.0)
        ->and((float) $user->balance)->toBe(1_500_000.0)
        ->and($user->fixedCosts()->count())->toBe(0)
        ->and($user->has_onboarded)->toBeTrue();
});

it('replaces previous fixed costs when onboarding is submitted again', function (): void {
    $user = User::factory()->create([
        'cycle_type' => CycleType::WEEKLY->value,
        'allowance_amount' => 500_000,
        'balance' => 400_000,
        'has_onboarded' => true,
    ]);

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Old Cost',
        'amount' => 50_000,
        'deduction_type' => DeductionType::IN->value,
        'cycle' => CycleType::WEEKLY->value,
    ]);

    $payload = [
        'budgetCycle' => CycleType::MONTHLY->value,
        'allowanceAmount' => 2_000_000,
        'fixedCosts' => [
            [
                'name' => 'Internet',
                'amount' => 300_000,
                'deductionType' => DeductionType::IN->value,
                'cycle' => CycleType::MONTHLY->value,
            ],
        ],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertOk();

    $user->refresh();

    expect((float) $user->balance)->toBe(1_700_000.0)
        ->and($user->fixedCosts()->count())->toBe(1)
        ->and($user->fixedCosts()->first()->name)->toBe('Internet')
        ->and(FixedCostTemplate::query()->where('name', 'Old Cost')->exists())->toBeFalse();
});

it('returns validation errors when payload is invalid', function (): void {
    $user = User::factory()->create();

    $payload = [
        'budgetCycle' => 'not-valid',
        'allowanceAmount' => 0,
        'fixedCosts' => [
            [
                'name' => '',
                'amount' => -100,
                'deductionType' => 'invalid',
                'cycle' => 'also-invalid',
            ],
        ],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'budgetCycle',
            'allowanceAmount',
            'fixedCosts.0.name',
            'fixedCosts.0.amount',
            'fixedCosts.0.deductionType',
            'fixedCosts.0.cycle',
        ], 'data');
});

it('returns validation error when total IN fixed costs exceeds allowance', function (): void {
    $user = User::factory()->create([
        'cycle_type' => CycleType::WEEKLY->value,
        'allowance_amount' => 700_000,
        'balance' => 500_000,
        'has_onboarded' => false,
    ]);

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Existing Cost',
        'amount' => 100_000,
        'deduction_type' => DeductionType::IN->value,
        'cycle' => CycleType::MONTHLY->value,
    ]);

    $payload = [
        'budgetCycle' => CycleType::MONTHLY->value,
        'allowanceAmount' => 100_000,
        'fixedCosts' => [
            [
                'name' => 'Rent',
                'amount' => 150_000,
                'deductionType' => DeductionType::IN->value,
                'cycle' => CycleType::MONTHLY->value,
            ],
        ],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['fixedCosts'], 'data');

    $user->refresh();

    expect($user->cycle_type->value)->toBe(CycleType::WEEKLY->value)
        ->and((float) $user->allowance_amount)->toBe(700_000.0)
        ->and((float) $user->balance)->toBe(500_000.0)
        ->and($user->fixedCosts()->count())->toBe(1)
        ->and($user->fixedCosts()->first()->name)->toBe('Existing Cost');
});

it('uses monthly as default cycle when fixed cost cycle is omitted or null', function (): void {
    $user = User::factory()->create([
        'balance' => 0,
        'has_onboarded' => false,
    ]);

    $payload = [
        'budgetCycle' => CycleType::MONTHLY->value,
        'allowanceAmount' => 1_000_000,
        'fixedCosts' => [
            [
                'name' => 'Rent',
                'amount' => 250_000,
                'deductionType' => DeductionType::IN->value,
                'cycle' => null,
            ],
        ],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertOk();

    $user->refresh();

    expect($user->fixedCosts()->count())->toBe(1)
        ->and($user->fixedCosts()->first()->cycle->value)->toBe(CycleType::MONTHLY->value)
        ->and((float) $user->balance)->toBe(750_000.0);
});

it('persists empty fixed costs array correctly', function (): void {
    $user = User::factory()->create([
        'balance' => 0,
        'has_onboarded' => false,
    ]);

    $payload = [
        'budgetCycle' => CycleType::MONTHLY->value,
        'allowanceAmount' => 900_000,
        'fixedCosts' => [],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertOk();

    $user->refresh();

    expect((float) $user->balance)->toBe(900_000.0)
        ->and((float) $user->allowance_amount)->toBe(900_000.0)
        ->and($user->fixedCosts()->count())->toBe(0);
});
