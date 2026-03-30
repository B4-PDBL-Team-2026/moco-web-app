<?php

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\CustomCategory;
use App\Models\Transaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guest cannot update transaction', function () {
    $this->putJson('/api/transaction/1', [])
        ->assertUnauthorized();
});

test('authenticated user cannot change transaction type (Rule 25)', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = CustomCategory::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => CustomCategory::class,
        'amount' => '200.00',
        'type' => 'expense',
    ]);

    $payload = ['type' => 'income'];

    $this->putJson("/api/transaction/{$transaction->id}", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type'], 'data');
});
