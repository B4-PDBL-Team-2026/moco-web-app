<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Transaction\Actions\DeleteBatchTransactionAction;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    [$this->user, $this->category] = setupUserWithBudget(['current_balance' => '5000.00']);
    $this->action = app(DeleteBatchTransactionAction::class);
});

test('throws ModelNotFoundException when batch does not belong to user', function () {
    $otherUser = User::factory()->create();
    $otherBatch = TransactionBatch::factory()->create(['user_id' => $otherUser->id]);

    expect(fn () => $this->action->execute($this->user->id, $otherBatch->id))
        ->toThrow(ModelNotFoundException::class);
});

test('throws BusinessRuleException when net income exceeds balance', function () {
    [$poorUser, $category] = setupUserWithBudget(['current_balance' => '50.00']);

    $batch = TransactionBatch::factory()->create(['user_id' => $poorUser->id]);
    Transaction::factory()->create([
        'transaction_batch_id' => $batch->id,
        'user_id' => $poorUser->id,
        'amount' => '200.00',
        'type' => TransactionType::INCOME->value,
    ]);
    Transaction::factory()->create([
        'transaction_batch_id' => $batch->id,
        'user_id' => $poorUser->id,
        'amount' => '80.00',
        'type' => TransactionType::EXPENSE->value,
    ]);

    // Net income = 120, balance = 50 → -70 → reject
    UserBudgetSnapshot::where('user_id', $poorUser->id)
        ->update(['current_balance' => '50.00']);

    expect(fn () => $this->action->execute($poorUser->id, $batch->id))
        ->toThrow(BusinessRuleException::class);
});

test('expense-only batch always deletes successfully', function () {
    [$user, $category] = setupUserWithBudget(['current_balance' => '0.01']);

    $batch = TransactionBatch::factory()->create(['user_id' => $user->id]);
    Transaction::factory()->create([
        'transaction_batch_id' => $batch->id,
        'user_id' => $user->id,
        'amount' => '999999.00',
        'type' => TransactionType::EXPENSE->value,
    ]);

    UserBudgetSnapshot::where('user_id', $user->id)
        ->update(['current_balance' => '0.01']);

    $this->action->execute($user->id, $batch->id);

    expect(TransactionBatch::find($batch->id))->toBeNull();
});

test('deletes all child transactions along with batch', function () {
    $batch = TransactionBatch::factory()->create(['user_id' => $this->user->id]);
    Transaction::factory()->count(3)->create([
        'transaction_batch_id' => $batch->id,
        'user_id' => $this->user->id,
        'amount' => '100.00',
        'type' => TransactionType::EXPENSE->value,
    ]);

    $this->action->execute($this->user->id, $batch->id);

    expect(TransactionBatch::find($batch->id))->toBeNull()
        ->and(Transaction::where('transaction_batch_id', $batch->id)->count())->toBe(0);
});

test('allows delete when balance exactly reaches zero', function () {
    [$user, $category] = setupUserWithBudget(['current_balance' => '100.00']);

    $batch = TransactionBatch::factory()->create(['user_id' => $user->id]);
    Transaction::factory()->create([
        'transaction_batch_id' => $batch->id,
        'user_id' => $user->id,
        'amount' => '100.00',
        'type' => TransactionType::INCOME->value,
    ]);

    UserBudgetSnapshot::where('user_id', $user->id)
        ->update(['current_balance' => '100.00']);

    $this->action->execute($user->id, $batch->id);

    expect(TransactionBatch::find($batch->id))->toBeNull();
});

test('rolls back if snapshot is missing', function () {
    $batch = TransactionBatch::factory()->create(['user_id' => $this->user->id]);
    Transaction::factory()->create([
        'transaction_batch_id' => $batch->id,
        'user_id' => $this->user->id,
        'amount' => '100.00',
        'type' => TransactionType::INCOME->value, // income → triggers snapshot check
    ]);

    UserBudgetSnapshot::where('user_id', $this->user->id)->delete();

    expect(fn () => $this->action->execute($this->user->id, $batch->id))
        ->toThrow(ModelNotFoundException::class)
        ->and(TransactionBatch::find($batch->id))->not->toBeNull()
        ->and(Transaction::where('transaction_batch_id', $batch->id)->count())->toBe(1);
});
