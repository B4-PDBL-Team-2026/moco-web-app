<?php

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\CustomCategory;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

test('guest cannot store transaction', function () {
    $this->postJson('/api/transaction', [])
        ->assertUnauthorized();
});

test('authenticated user can create income transaction', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '5000.00',
    ]);

    $category = CustomCategory::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::INCOME,
    ]);

    $payload = [
        'categoryId' => $category->id,
        'categoryType' => 'custom',
        'name' => 'Salary',
        'amount' => '1000.00',
        'type' => 'income',
        'transactionDate' => now()->toDateString(),
    ];

    $response = $this->postJson('/api/transaction', $payload);
    $response->assertCreated();
    $this->assertDatabaseHas('transactions', ['name' => 'Salary']);
});
