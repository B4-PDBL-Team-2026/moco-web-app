<?php

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guest cannot delete transaction', function () {
    $this->deleteJson('/api/transaction/transactions/1')
        ->assertUnauthorized();
});

test('authenticated user can delete own expense transaction and balance is restored', function () {
    $user = User::factory()->create([
        'balance' => '800.00',
    ]);

    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => TransactionType::EXPENSE,
        'amount' => '200.00',
    ]);

    $this->deleteJson('/api/transaction/transactions/'.$transaction->id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Transaction deleted successfully.');

    $this->assertDatabaseMissing('transactions', [
        'id' => $transaction->id,
    ]);

    expect($user->fresh()->balance)->toBe('1000.00');
});

test('authenticated user can delete own income transaction and balance is reduced', function () {
    $user = User::factory()->create([
        'balance' => '1500.00',
    ]);

    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::INCOME,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => TransactionType::INCOME,
        'amount' => '500.00',
    ]);

    $this->deleteJson('/api/transaction/transactions/'.$transaction->id)
        ->assertOk();

    expect($user->fresh()->balance)->toBe('1000.00');
});

test('authenticated user cannot delete other users transaction', function () {
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

    $this->deleteJson('/api/transaction/transactions/'.$transaction->id)
        ->assertForbidden();
});
