<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Actions\UpdateBatchTransactionAction;
use App\Domains\Transaction\DTOs\UpdateBatchTransactionData;
use App\Domains\Transaction\DTOs\UpdateBatchTransactionItemData;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionBatch;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->action = app(UpdateBatchTransactionAction::class);
});

it('successfully updates batch and replaces items completely', function () {
    [$user, $category] = setupUserWithBudget(['current_balance' => '5000.00']);

    $batch = TransactionBatch::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Grocery',
        'total_amount' => 100.00,
    ]);

    Transaction::factory()->create([
        'transaction_batch_id' => $batch->id,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => TransactionType::EXPENSE->value,
        'amount' => 100.00,
    ]);

    $newCategory = Category::factory()->expense()->create();

    $dto = new UpdateBatchTransactionData(
        name: 'New Grocery',
        note: 'Updated notes',
        transactionAt: CarbonImmutable::parse('2026-05-10 10:00:00', 'UTC'),
        source: TransactionSource::MANUAL,
        items: [
            new UpdateBatchTransactionItemData(
                name: 'Item 1',
                amount: '150.00',
                categoryId: $newCategory->id,
                type: TransactionType::EXPENSE,
                note: 'Note 1'
            ),
            new UpdateBatchTransactionItemData(
                name: 'Item 2',
                amount: '50.00',
                categoryId: $newCategory->id,
                type: TransactionType::EXPENSE,
            ),
        ]
    );

    $updatedBatch = $this->action->execute($user->id, $batch->id, $dto);

    expect($updatedBatch->name)->toBe('New Grocery')
        ->and($updatedBatch->note)->toBe('Updated notes')
        ->and((float) $updatedBatch->total_amount)->toBe(200.00)
        ->and($updatedBatch->transactions)->toHaveCount(2);

    $this->assertDatabaseHas('transactions', [
        'transaction_batch_id' => $batch->id,
        'name' => 'Item 1',
        'amount' => 150.00,
    ]);
});

it('calculates total amount correctly when mixing income and expense items', function () {
    [$user, $category] = setupUserWithBudget(['current_balance' => '1000.00']);

    $batch = TransactionBatch::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 0.00,
    ]);

    $dto = new UpdateBatchTransactionData(
        name: 'Mixed Batch',
        note: null,
        transactionAt: now()->toImmutable(),
        source: TransactionSource::MANUAL,
        items: [
            new UpdateBatchTransactionItemData('Expense Item', '500.00', $category->id, TransactionType::EXPENSE),
            new UpdateBatchTransactionItemData('Income Item', '200.00', $category->id, TransactionType::INCOME),
        ]
    );

    $updatedBatch = $this->action->execute($user->id, $batch->id, $dto);

    // -500 + 200 = -300 -> abs(-300) = 300
    expect((float) $updatedBatch->total_amount)->toBe(300.00);
});

it('throws BusinessRuleException when update causes negative balance', function () {
    [$user, $category] = setupUserWithBudget(['current_balance' => '200.00']);

    $batch = TransactionBatch::factory()->create(['user_id' => $user->id]);

    Transaction::factory()->create([
        'transaction_batch_id' => $batch->id,
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE->value,
        'amount' => 50.00,
    ]);

    $dto = new UpdateBatchTransactionData(
        name: 'Expensive Update',
        note: null,
        transactionAt: now()->toImmutable(),
        source: null,
        items: [
            new UpdateBatchTransactionItemData('Over Budget', '500.00', $category->id, TransactionType::EXPENSE),
        ]
    );

    $this->action->execute($user->id, $batch->id, $dto);
})->throws(BusinessRuleException::class, 'balance_insufficient');

it('succeeds when update increases net balance', function () {
    [$user, $category] = setupUserWithBudget(['current_balance' => '100.00']);

    $batch = TransactionBatch::factory()->create(['user_id' => $user->id]);

    Transaction::factory()->create([
        'transaction_batch_id' => $batch->id,
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE->value,
        'amount' => 500.00,
    ]);

    $dto = new UpdateBatchTransactionData(
        name: 'Refund Update',
        note: null,
        transactionAt: now()->toImmutable(),
        source: null,
        items: [
            new UpdateBatchTransactionItemData('Refunded', '200.00', $category->id, TransactionType::INCOME),
        ]
    );

    $updatedBatch = $this->action->execute($user->id, $batch->id, $dto);

    expect((float) $updatedBatch->total_amount)->toBe(200.00);
});
