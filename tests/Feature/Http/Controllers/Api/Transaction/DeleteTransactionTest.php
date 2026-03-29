<?php

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\CustomCategory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('guest cannot delete transaction', function () {
    $this->deleteJson('/api/transaction/transactions/1')
        ->assertUnauthorized();
});

test('authenticated user can delete own expense transaction', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    \App\Models\UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    \App\Models\UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '500.00',
    ]);

    $category = CustomCategory::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => CustomCategory::class,
        'amount' => '100.00',
        'type' => 'expense',
    ]);

    $this->deleteJson("/api/transaction/transactions/{$transaction->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
});
