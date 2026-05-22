<?php

use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    [$this->user, $this->category] = setupUserWithBudget(['current_balance' => '5000.00']);
    $this->batch = TransactionBatch::factory()->create(['user_id' => $this->user->id]);

    UserBudgetSnapshot::where('user_id', $this->user->id)
        ->update(['recalculated_at' => now()->utc()]);
});

test('guest cannot delete batch transaction', function () {
    $this->deleteJson("/api/transaction/batch/{$this->batch->id}")
        ->assertUnauthorized();
});

test('user cannot delete another users batch transaction', function () {
    $otherUser = User::factory()->create();
    $otherBatch = TransactionBatch::factory()->create(['user_id' => $otherUser->id]);

    Sanctum::actingAs($this->user);

    $this->deleteJson("/api/transaction/batch/{$otherBatch->id}")
        ->assertNotFound();
});

test('fails when net income of batch exceeds current balance', function () {
    [$poorUser, $category] = setupUserWithBudget(['current_balance' => '50.00']);
    $batch = TransactionBatch::factory()->create(['user_id' => $poorUser->id]);

    DB::table('transactions')->insert([
        [
            'transaction_batch_id' => $batch->id,
            'user_id' => $poorUser->id,
            'category_id' => $category->id,
            'amount' => '200.00',
            'type' => TransactionType::INCOME->value,
            'source' => 'manual',
            'name' => 'Income Item',
            'transaction_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'transaction_batch_id' => $batch->id,
            'user_id' => $poorUser->id,
            'category_id' => $category->id,
            'amount' => '80.00',
            'type' => TransactionType::EXPENSE->value,
            'source' => 'manual',
            'name' => 'Expense Item',
            'transaction_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    UserBudgetSnapshot::where('user_id', $poorUser->id)->update([
        'current_balance' => '50.00',
        'recalculated_at' => now()->utc(),
    ]);

    Sanctum::actingAs($poorUser);

    $this->deleteJson("/api/transaction/batch/{$batch->id}")
        ->assertStatus(422);
});

test('expense-only batch can always be deleted regardless of balance', function () {
    [$poorUser, $category] = setupUserWithBudget(['current_balance' => '0.01']);
    $batch = TransactionBatch::factory()->create(['user_id' => $poorUser->id]);

    DB::table('transactions')->insert([
        'transaction_batch_id' => $batch->id,
        'user_id' => $poorUser->id,
        'category_id' => $category->id,
        'amount' => '999999.00',
        'type' => TransactionType::EXPENSE->value,
        'source' => 'manual',
        'name' => 'Big Expense',
        'transaction_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    UserBudgetSnapshot::where('user_id', $poorUser->id)->update([
        'current_balance' => '0.01',
        'recalculated_at' => now()->utc(),
    ]);

    Sanctum::actingAs($poorUser);

    $this->deleteJson("/api/transaction/batch/{$batch->id}")->dump()
        ->assertNoContent();

    $this->assertSoftDeleted('transaction_batches', ['id' => $batch->id]);
});

test('successfully deletes batch and all its child transactions', function () {
    DB::table('transactions')->insert([
        [
            'transaction_batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'amount' => '50.00',
            'type' => TransactionType::EXPENSE->value,
            'source' => 'manual',
            'name' => 'Expense 1',
            'transaction_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'transaction_batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'amount' => '50.00',
            'type' => TransactionType::EXPENSE->value,
            'source' => 'manual',
            'name' => 'Expense 2',
            'transaction_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    Sanctum::actingAs($this->user);

    $this->deleteJson("/api/transaction/batch/{$this->batch->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('transaction_batches', ['id' => $this->batch->id]);
    $this->assertSoftDeleted('transactions', ['transaction_batch_id' => $this->batch->id]);
});

test('successfully deletes empty batch', function () {
    Sanctum::actingAs($this->user);

    $this->deleteJson("/api/transaction/batch/{$this->batch->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('transaction_batches', ['id' => $this->batch->id]);
});

test('allows delete when balance exactly reaches zero after deletion', function () {
    [$user, $category] = setupUserWithBudget(['current_balance' => '100.00']);
    $batch = TransactionBatch::factory()->create(['user_id' => $user->id]);

    DB::table('transactions')->insert([
        'transaction_batch_id' => $batch->id,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => '100.00',
        'type' => TransactionType::INCOME->value,
        'source' => 'manual',
        'name' => 'Income Item',
        'transaction_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    UserBudgetSnapshot::where('user_id', $user->id)->update([
        'current_balance' => '100.00',
        'recalculated_at' => now()->utc(),
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/transaction/batch/{$batch->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('transaction_batches', ['id' => $batch->id]);
});
