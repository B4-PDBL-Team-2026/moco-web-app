<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

test('guest cannot delete transaction', function () {
    $this->deleteJson('/api/transaction/999')
        ->assertUnauthorized();
});

test('authenticated user can delete own expense transaction', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '500.00',
    ]);

    $category = Category::factory()->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => '100.00',
        'type' => 'expense',
    ]);

    $this->deleteJson("/api/transaction/{$transaction->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
});
