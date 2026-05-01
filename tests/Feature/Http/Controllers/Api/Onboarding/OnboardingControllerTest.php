<?php

use App\Domains\Category\Models\Category;
use App\Domains\User\Models\User;
use Database\Seeders\DatabaseSeeder;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('unauthenticated user cannot access onboarding endpoint', function () {
    $payload = [
        'budgetCycle' => 'monthly',
        'initialBalance' => 1000,
        'flooringLimit' => 0,
        'ceilingLimit' => 999999,
        'timezone' => 'Asia/Jakarta',
    ];

    $this->postJson('/api/onboarding', $payload)
        ->assertUnauthorized();
});

test('user onboarding fails when required payload is missing', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['budgetCycle', 'initialBalance', 'fixedCosts', 'ceilingLimit', 'flooringLimit']);
});

test('user onboarding fails when budget cycle enum is invalid', function () {
    $user = User::factory()->create();

    $payload = [
        'budgetCycle' => 'yearly',
        'initialBalance' => 1000,
        'flooringLimit' => 0,
        'ceilingLimit' => null,
        'timezone' => 'Asia/Jakarta',
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['budgetCycle']);
});

test('user should be able to complete onboarding with fixed costs', function () {
    $user = User::factory()->create();
    $category = Category::factory()->expense()->create();

    $payload = [
        'budgetCycle' => 'monthly',
        'initialBalance' => 5000000,
        'flooringLimit' => 50000,
        'ceilingLimit' => 50000,
        'timezone' => 'Asia/Jakarta',
        'fixedCosts' => [
            [
                'name' => 'Kosan',
                'amount' => 1000000,
                'cycleType' => 'monthly',
                'dueDay' => 25,
                'categoryId' => $category->id,
            ],
            [
                'name' => 'Air Mingguan',
                'amount' => 50000,
                'cycleType' => 'weekly',
                'dueDay' => 3,
                'categoryId' => $category->id,
            ],
        ],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertOk()
        ->assertJsonPath('data.fixedCostsCount', 2)
        ->assertJsonPath('data.currentBalance', '5000000.00');

    assertDatabaseHas('users', [
        'id' => $user->id,
        'has_onboarded' => true,
    ]);
});

test('user onboarding fails when budget cycle and fixed cost cycle are incompatible', function () {
    $user = User::factory()->create();
    $category = Category::factory()->expense()->create();

    $payload = [
        'budgetCycle' => 'weekly',
        'initialBalance' => 1000,
        'flooringLimit' => 0,
        'ceilingLimit' => 999999,
        'timezone' => 'Asia/Jakarta',
        'fixedCosts' => [
            [
                'name' => 'Monthly Rent',
                'amount' => 10000,
                'cycleType' => 'monthly',
                'dueDay' => 25,
                'categoryId' => $category->id,
            ],
        ],
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrorFor('fixedCosts.0.cycleType');
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
        ->postJson('/api/onboarding', $payload)
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
            ],
        ],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/onboarding', $payload)
        ->assertUnprocessable();
});
