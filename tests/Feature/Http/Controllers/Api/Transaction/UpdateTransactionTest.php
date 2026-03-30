<?php

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\CustomCategory;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $this->user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $this->user->id,
        'current_balance' => '1000.00',
    ]);
});

test('guest cannot update transaction', function () {
    $this->putJson('/api/transaction/1', [])
        ->assertUnauthorized();
});

test('user cannot update another users transaction', function () {
    $otherUser = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Secret Transaction',
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'name' => 'Hacked Name',
    ])->assertStatus(403);
});

test('successfully updates transaction name and note', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Old Name',
        'note' => 'Old Note',
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'name' => 'New Name',
        'note' => 'New Note',
    ])->assertOk() // 200
        ->assertJsonPath('data.name', 'New Name');

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'name' => 'New Name',
        'note' => 'New Note',
    ]);
});

test('successfully updates transaction amount', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'amount' => '100.00',
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'amount' => '350.50',
    ])->assertOk();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'amount' => '350.50',
    ]);
});

test('successfully updates category to a system category', function () {
    $oldCategory = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE->value]);
    $newCategory = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE->value]);

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $oldCategory->id,
        'category_type' => 'system',
        'type' => TransactionType::EXPENSE->value,
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'categoryId' => $newCategory->id,
        'categoryType' => 'system',
    ])->assertOk();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'category_id' => $newCategory->id,
        'category_type' => SystemCategory::class,
    ]);
});

test('successfully updates category to a custom category', function () {
    $sysCategory = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE->value]);

    $customCategory = CustomCategory::factory()->create([
        'user_id' => $this->user->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $sysCategory->id,
        'category_type' => 'system',
        'type' => TransactionType::EXPENSE->value,
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'categoryId' => $customCategory->id,
        'categoryType' => 'custom',
    ])->assertOk();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'category_id' => $customCategory->id,
        'category_type' => CustomCategory::class,
    ]);
});

test('validation fails if categoryType is missing when categoryId is provided', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'categoryId' => 1,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['categoryType'], 'data');
});

test('validation fails if user tries to use another users custom category', function () {
    $otherUser = User::factory()->create();
    $hackedCategory = CustomCategory::factory()->create([
        'user_id' => $otherUser->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'categoryId' => $hackedCategory->id,
        'categoryType' => 'custom',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['categoryId'], 'data');
});

test('validation fails if category type does not match transaction type', function () {
    $expenseCategory = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE->value]);
    $incomeCategory = SystemCategory::factory()->create(['type' => TransactionType::INCOME->value]);

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $expenseCategory->id,
        'category_type' => 'system',
        'type' => TransactionType::EXPENSE->value,
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'categoryId' => $incomeCategory->id,
        'categoryType' => 'system',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['categoryId'], 'data');
});

test('updates transaction date without throwing errors', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'transaction_date' => '2026-03-01',
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'transactionDate' => '2026-03-15',
    ])->assertOk();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'transaction_date' => '2026-03-15',
    ]);
});
