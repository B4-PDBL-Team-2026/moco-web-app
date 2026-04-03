<?php

use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

test('guest cannot access dashboard', function () {
    $this->getJson('/api/user/dashboard')
        ->assertUnauthorized();
});

test('authenticated user can access dashboard', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'timezone' => 'Asia/Jakarta',
    ]);
    UserBudgetSnapshot::factory()->create(['user_id' => $user->id]);

    $response = $this->getJson('/api/user/dashboard');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'serverTime',
                'currentBalance',
                'budgetCycle',
                'safetyCeiling',
                'safetyFlooring',
                'todaySpent',
                'todayLimit',
                'tomorrowLimitPrediction',
                'rawTodayLimit',
                'unpaidFixedCosts',
            ],
        ])
        ->assertJsonPath('success', true);
});

test('returns 404 when user has no budget snapshot', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/user/dashboard')
        ->assertNotFound();
});
