<?php

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guest cannot update transaction', function () {
    $this->putJson('/api/transaction/transactions/1', [])
        ->assertUnauthorized();
});

test('authenticated user can update own transaction', function () {
    $user = User::factory()->create([
        'balance' => '900.00',
    ]);

    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Old Groceries',
        'amount' => '100.00',
        'type' => TransactionType::EXPENSE,
        'transaction_date' => '2026-03-01',
    ]);

    $payload = [
        'name' => 'New Groceries',
        'amount' => '250.00',
        'transactionDate' => '2026-03-16',
    ];

    $this->putJson('/api/transaction/transactions/'.$transaction->id, $payload)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Transaction updated successfully.');

    $transaction->refresh();

    expect($transaction->name)->toBe('New Groceries')
        ->and((string) $transaction->amount)->toBe('250.00')
        ->and($transaction->transaction_date->toDateString())->toBe('2026-03-16')
        ->and($user->fresh()->balance)->toBe('750.00');
});

test('authenticated user can update transaction type and category', function () {
    $user = User::factory()->create([
        'balance' => '900.00',
    ]);

    Sanctum::actingAs($user);

    $expenseCategory = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $incomeCategory = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::INCOME,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $expenseCategory->id,
        'type' => TransactionType::EXPENSE,
        'amount' => '100.00',
    ]);

    $payload = [
        'type' => TransactionType::INCOME->value,
        'categoryId' => $incomeCategory->id,
    ];

    $this->putJson('/api/transaction/transactions/'.$transaction->id, $payload)
        ->assertOk();

    expect($transaction->fresh()->type)->toBe(TransactionType::INCOME);
    expect($user->fresh()->balance)->toBe('1100.00');
});

test('update endpoint validates amount when provided', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $this->putJson('/api/transaction/transactions/'.$transaction->id, [
        'amount' => '0',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['amount'], 'data');
});

test('update endpoint validates category belongs to authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    $ownCategory = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $otherCategory = Category::factory()->create([
        'user_id' => $otherUser->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $ownCategory->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $this->putJson('/api/transaction/transactions/'.$transaction->id, [
        'categoryId' => $otherCategory->id,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['categoryId'], 'data');
});

test('update endpoint rejects mismatched category type and transaction type', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $expenseCategory = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $incomeCategory = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::INCOME,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $expenseCategory->id,
        'type' => TransactionType::EXPENSE,
        'amount' => '100.00',
    ]);

    $this->putJson('/api/transaction/transactions/'.$transaction->id, [
        'type' => TransactionType::EXPENSE->value,
        'categoryId' => $incomeCategory->id,
    ])->assertUnprocessable();
});

test('authenticated user cannot update other users transaction', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $otherUser->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $otherUser->id,
        'category_id' => $category->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $this->putJson('/api/transaction/transactions/'.$transaction->id, [
        'name' => 'Hacked',
    ])->assertForbidden();
});
