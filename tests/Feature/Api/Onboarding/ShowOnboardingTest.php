<?php

declare(strict_types=1);

use App\Enums\CycleType;
use App\Enums\DeductionType;
use App\Models\FixedCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to show onboarding data', function (): void {
    $this->getJson('/api/onboarding')
        ->assertUnauthorized();
});

it('returns onboarding form and summary data for authenticated user', function (): void {
    $user = User::factory()->create([
        'cycle_type' => CycleType::MONTHLY->value,
        'allowance_amount' => 3_000_000,
        'balance' => 2_000_000,
        'has_onboarded' => true,
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

    $otherUser = User::factory()->create();

    FixedCost::query()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other User Cost',
        'amount' => 999_999,
        'deduction_type' => DeductionType::IN->value,
        'cycle' => CycleType::MONTHLY->value,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/onboarding');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'form' => [
                    'budgetCycle',
                    'allowanceAmount',
                    'fixedCosts' => [
                        '*' => [
                            'id',
                            'name',
                            'amount',
                            'deductionType',
                            'cycle',
                        ],
                    ],
                ],
                'summary' => [
                    'balance',
                    'totalFixedCosts',
                    'dailyLimit',
                ],
            ],
        ]);

    $data = $response->json('data');

    expect($data['form']['budgetCycle'])->toBe(CycleType::MONTHLY->value)
        ->and((float) $data['form']['allowanceAmount'])->toBe(3_000_000.0)
        ->and((float) $data['summary']['balance'])->toBe(2_000_000.0)
        ->and((float) $data['summary']['totalFixedCosts'])->toBe(1_300_000.0)
        ->and((float) $data['summary']['dailyLimit'])
        ->toEqualWithDelta(2_000_000 / CycleType::MONTHLY->countDays(), 0.0001)
        ->and($data['form']['fixedCosts'])->toHaveCount(2);
});

it('returns empty fixed costs and zero total when user has no fixed costs', function (): void {
    $user = User::factory()->create([
        'cycle_type' => CycleType::WEEKLY->value,
        'allowance_amount' => 700_000,
        'balance' => 500_000,
        'has_onboarded' => false,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/onboarding');

    $response
        ->assertOk()
        ->assertJsonPath('data.form.fixedCosts', [])
        ->assertJsonPath('data.form.budgetCycle', CycleType::WEEKLY->value)
        ->assertJsonPath('data.summary.totalFixedCosts', 0);

    expect((float) $response->json('data.summary.dailyLimit'))
        ->toEqualWithDelta(500_000 / CycleType::WEEKLY->countDays(), 0.0001);
});

it('only returns authenticated users onboarding data', function (): void {
    $user = User::factory()->create([
        'cycle_type' => CycleType::MONTHLY->value,
        'allowance_amount' => 1_000_000,
        'balance' => 900_000,
    ]);

    $otherUser = User::factory()->create([
        'cycle_type' => CycleType::WEEKLY->value,
        'allowance_amount' => 9_000_000,
        'balance' => 8_000_000,
    ]);

    FixedCost::query()->create([
        'user_id' => $user->id,
        'name' => 'User Cost',
        'amount' => 100_000,
        'deduction_type' => DeductionType::IN->value,
        'cycle' => CycleType::MONTHLY->value,
    ]);

    FixedCost::query()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other User Cost',
        'amount' => 500_000,
        'deduction_type' => DeductionType::IN->value,
        'cycle' => CycleType::WEEKLY->value,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/onboarding');

    $fixedCosts = $response->json('data.form.fixedCosts');

    expect($fixedCosts)->toHaveCount(1)
        ->and($fixedCosts[0]['name'])->toBe('User Cost')
        ->and((float) $response->json('data.summary.totalFixedCosts'))->toBe(100_000.0);
});
