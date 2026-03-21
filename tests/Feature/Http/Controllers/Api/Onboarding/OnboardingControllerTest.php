<?php

use App\Models\SystemCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('user should be able to stores onboarding and get final onboarding result', function () {
    $user = User::factory()->create();

    $payload = [
        'budgetCycle' => 'monthly',
        'initialBalance' => 1000,
        'flooringLimit' => 0,
        'ceilingLimit' => 999999,
        'timezone' => 'Asia/Jakarta',
        'fixedCosts' => [],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)->dump()
        ->assertOk()
        ->assertJsonPath('message', 'Onboarding completed successfully.')
        ->assertJsonStructure([
            'message',
            'data' => [
                'userId',
                'cycleType',
                'currentBalance',
                'reservedCost',
                'dailyAllowance',
                'cycleKey',
                'cycleStartDate',
                'cycleEndDate',
                'remainingDays',
                'fixedCostsCount',
                'hasOnboarded',
            ],
        ]);
});

test('user onboarding fails when weekly due day is invalid', function () {
    $user = User::factory()->create();

    $payload = [
        'budgetCycle' => 'monthly',
        'initialBalance' => 1000,
        'flooringLimit' => 0,
        'ceilingLimit' => 999999,
        'fixedCosts' => [
            [
                'name' => 'Bad Weekly',
                'amount' => 10000,
                'cycleType' => 'weekly',
                'dueDay' => 8,
                'categoryId' => 1,
                'categoryType' => SystemCategory::class,
            ],
        ],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertUnprocessable();
});
