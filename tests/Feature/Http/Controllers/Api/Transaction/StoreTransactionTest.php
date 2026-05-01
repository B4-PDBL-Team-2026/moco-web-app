<?php

use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\User\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\Sanctum;

test('guest cannot store transaction', function () {
    $this->postJson('/api/transaction', [])
        ->assertUnauthorized();
});

test('authenticated user can create income transaction', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-04 12:00:00', 'UTC'));
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '5000.00',
    ]);

    $category = Category::factory()->income()->create();

    $payload = [
        'categoryId' => $category->id,
        'name' => 'Salary',
        'amount' => '1000.00',
        'type' => 'income',
        'transactionAt' => '2026-04-04T10:00:00Z',
    ];

    $response = $this->postJson('/api/transaction', $payload);
    $response->assertCreated();
    $this->assertDatabaseHas('transactions', [
        'name' => 'Salary',
        'transaction_at' => '2026-04-04 10:00:00',
    ]);
});

test('authenticated user can create income transaction and timezone is converted to UTC', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '5000.00',
    ]);

    $category = Category::factory()->income()->create();

    // user at Asia/Jakarta
    $payload = [
        'categoryId' => $category->id,
        'name' => 'Salary',
        'amount' => '1000.00',
        'type' => 'income',
        'transactionAt' => '2026-04-04T13:00:00+07:00',
    ];

    $response = $this->postJson('/api/transaction', $payload);

    $response->assertCreated();

    // should be stored as UTC timezone
    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'name' => 'Salary',
        'transaction_at' => '2026-04-04 06:00:00',
    ]);
});
