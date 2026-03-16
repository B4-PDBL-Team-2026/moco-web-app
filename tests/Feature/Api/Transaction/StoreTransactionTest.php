<?php

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guest cannot store transaction', function () {
    $this->postJson('/api/transaction/transactions', [])
        ->assertUnauthorized();
});

test('authenticated user can create income transaction', function () {
    $user = User::factory()->create([
        'balance' => '1000.00',
    ]);

    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::INCOME,
    ]);

    $payload = [
        'name' => 'Salary',
        'amount' => '500.00',
        'type' => TransactionType::INCOME->value,
        'note' => 'Monthly salary',
        'transactionDate' => '2026-03-16',
        'categoryId' => $category->id,
    ];

    $response = $this->postJson('/api/transaction/transactions', $payload);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Transaction created successfully.');

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Salary',
        'amount' => '500.00',
        'type' => TransactionType::INCOME->value,
    ]);

    expect($user->fresh()->balance)->toBe('1500.00');
});

test('authenticated user can create expense transaction', function () {
    $user = User::factory()->create([
        'balance' => '1000.00',
    ]);

    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $payload = [
        'name' => 'Groceries',
        'amount' => '250.00',
        'type' => TransactionType::EXPENSE->value,
        'note' => 'Weekly groceries',
        'transactionDate' => '2026-03-16',
        'categoryId' => $category->id,
    ];

    $this->postJson('/api/transaction/transactions', $payload)
        ->assertOk();

    expect($user->fresh()->balance)->toBe('750.00');
});

test('store endpoint validates required fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/transaction/transactions', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'amount',
            'type',
            'transactionDate',
            'categoryId',
        ], 'data');
});

test('store endpoint validates amount must be greater than zero', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $payload = [
        'name' => 'Groceries',
        'amount' => '0',
        'type' => TransactionType::EXPENSE->value,
        'transactionDate' => '2026-03-16',
        'categoryId' => $category->id,
    ];

    $this->postJson('/api/transaction/transactions', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount'], 'data');
});

test('store endpoint validates type enum', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $payload = [
        'name' => 'Groceries',
        'amount' => '100.00',
        'type' => 'invalid-type',
        'transactionDate' => '2026-03-16',
        'categoryId' => $category->id,
    ];

    $this->postJson('/api/transaction/transactions', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type'], 'data');
});

test('store endpoint validates category belongs to authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $otherUser->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $payload = [
        'name' => 'Groceries',
        'amount' => '100.00',
        'type' => TransactionType::EXPENSE->value,
        'transactionDate' => '2026-03-16',
        'categoryId' => $category->id,
    ];

    $this->postJson('/api/transaction/transactions', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['categoryId'], 'data');
});

test('store endpoint returns validation error when category type mismatches transaction type', function () {
    $user = User::factory()->create([
        'balance' => '1000.00',
    ]);

    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $payload = [
        'name' => 'Salary',
        'amount' => '500.00',
        'type' => TransactionType::INCOME->value,
        'transactionDate' => '2026-03-16',
        'categoryId' => $category->id,
    ];

    $this->postJson('/api/transaction/transactions', $payload)
        ->assertUnprocessable();
});
