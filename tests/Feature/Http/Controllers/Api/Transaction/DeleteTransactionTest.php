<?php

use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
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
