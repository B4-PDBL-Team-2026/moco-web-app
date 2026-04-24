<?php

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
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
    ])->assertOk()
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
    $oldCategory = Category::factory()->expense()->create();
    $newCategory = Category::factory()->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $oldCategory->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'categoryId' => $newCategory->id,
    ])->assertOk();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'category_id' => $newCategory->id,
    ]);
});

test('successfully updates category to a custom category', function () {
    $sysCategory = Category::factory()->expense()->create();

    $customCategory = Category::factory()->custom($this->user)->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $sysCategory->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'categoryId' => $customCategory->id,
    ])->assertOk();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'category_id' => $customCategory->id,
    ]);
});

test('validation fails if user tries to use another users custom category', function () {
    $otherUser = User::factory()->create();
    $hackedCategory = Category::factory()->custom($otherUser)->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'categoryId' => $hackedCategory->id,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['businessRule'], 'data');
});

test('validation fails if category type does not match transaction type', function () {
    $expenseCategory = Category::factory()->expense()->create();
    $incomeCategory = Category::factory()->income()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $expenseCategory->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'categoryId' => $incomeCategory->id,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['businessRule'], 'data');
});

test('successfully updates transaction date', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'transaction_at' => '2026-03-01',
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'transactionAt' => '2026-03-15',
    ])->assertOk();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'transaction_at' => '2026-03-15 00:00:00',
    ]);
});

test('fails to update transaction to a future date due to validation', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-04 12:00:00', 'UTC'));

    $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'transactionAt' => '2026-04-04T15:00:00Z',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['transactionAt'], 'data');
});

test('feature: verifies UTC conversion when updating date via API', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-04 23:00:00', 'UTC'));

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'transaction_at' => '2026-04-04 00:00:00',
    ]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/{$transaction->id}", [
        'transactionAt' => '2026-04-04T22:00:00+07:00',
    ])->assertOk();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'transaction_at' => '2026-04-04 15:00:00',
    ]);
});
