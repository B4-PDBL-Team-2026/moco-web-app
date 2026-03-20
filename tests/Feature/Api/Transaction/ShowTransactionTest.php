<?php

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guest cannot show transaction', function () {
    $this->getJson('/api/transaction/transactions/1')
        ->assertUnauthorized();
});

test('authenticated user can show own transaction', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = SystemCategory::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Groceries',
    ]);

    $this->getJson('/api/transaction/transactions/'.$transaction->id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Transaction retrieved successfully.')
        ->assertJsonPath('data.id', $transaction->id)
        ->assertJsonPath('data.name', 'Groceries');
});

test('authenticated user cannot show other users transaction', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    $category = SystemCategory::factory()->create([
        'user_id' => $otherUser->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $otherUser->id,
        'category_id' => $category->id,
    ]);

    $this->getJson('/api/transaction/transactions/'.$transaction->id)
        ->assertForbidden();
});
