<?php

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\CustomCategory;
use App\Models\Transaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guest cannot show transaction', function () {
    $this->getJson('/api/transaction/1')
        ->assertUnauthorized();
});

test('authenticated user can show own transaction', function () {
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
        'amount' => '500.00',
        'type' => 'income',
    ]);

    $this->getJson("/api/transaction/{$transaction->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $transaction->id);
});
