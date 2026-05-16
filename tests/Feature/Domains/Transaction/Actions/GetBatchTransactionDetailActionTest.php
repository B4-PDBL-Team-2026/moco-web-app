<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Actions\GetBatchTransactionDetailAction;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\User\Models\User;

beforeEach(function () {
    $this->action = app(GetBatchTransactionDetailAction::class);
    $this->user = User::factory()->create();
});

it('successfully retrieves a batch transaction and eager loads relations', function () {
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    $batch = TransactionBatch::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Belanja Bulanan',
        'note' => 'Monthly supplies',
    ]);

    Transaction::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'transaction_batch_id' => $batch->id,
        'category_id' => $category->id,
        'type' => 'expense',
    ]);

    $result = $this->action->execute($this->user->id, $batch->id);

    expect($result->id)->toBe($batch->id)
        ->and($result->name)->toBe('Belanja Bulanan')
        ->and($result->note)->toBe('Monthly supplies')
        ->and($result->relationLoaded('transactions'))->toBeTrue()
        ->and($result->transactions->first()->relationLoaded('category'))->toBeTrue()
        ->and($result->transactions)->toHaveCount(2);
});

it('retrieves batch transaction even if it has no child transactions', function () {
    $batch = TransactionBatch::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $result = $this->action->execute($this->user->id, $batch->id);

    expect($result->id)->toBe($batch->id)
        ->and($result->relationLoaded('transactions'))->toBeTrue()
        ->and($result->transactions)->toHaveCount(0);
});

it('throws an exception if the batch transaction does not exist', function () {
    $fakeBatchId = 99999;

    expect(fn () => $this->action->execute($this->user->id, $fakeBatchId))
        ->toThrow(BusinessRuleException::class);
});

it('throws an exception if the batch transaction belongs to another user', function () {
    $otherUser = User::factory()->create();

    $otherUserBatch = TransactionBatch::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    expect(fn () => $this->action->execute($this->user->id, $otherUserBatch->id))
        ->toThrow(BusinessRuleException::class);
});
