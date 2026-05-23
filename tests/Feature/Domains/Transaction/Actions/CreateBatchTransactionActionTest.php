<?php

use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Actions\CreateBatchTransactionAction;
use App\Domains\Transaction\DTOs\CreateBatchTransactionData;
use App\Domains\Transaction\DTOs\CreateBatchTransactionItemData;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\User\Models\User;

it('successfully stores a batch and its items with correct total amount', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $data = new CreateBatchTransactionData(
        name: 'Belanja Indomaret',
        note: 'Beli bulanan',
        transactionAt: '2026-05-15 10:00:00',
        source: TransactionSource::BATCH,
        items: [
            new CreateBatchTransactionItemData(name: 'Indomie Goreng', amount: '15000.50', categoryId: $category->id, type: TransactionType::EXPENSE),
            new CreateBatchTransactionItemData(name: 'Telur Ayam', amount: '25000.00', categoryId: $category->id, type: TransactionType::EXPENSE, note: 'Beli 1 Kg'),
        ]
    );

    $action = app(CreateBatchTransactionAction::class);
    $batch = $action->execute($user->id, $data);

    expect($batch)->toBeInstanceOf(TransactionBatch::class)
        ->and($batch->relationLoaded('transactions'))->toBeTrue()
        ->and($batch->transactions)->toHaveCount(2)
        ->and((float) $batch->amount)->toBe(40000.50)
        ->and($batch->name)->toBe('Belanja Indomaret')
        ->and($batch->note)->toBe('Beli bulanan')
        ->and($batch->type)->toBe('expense');

    $this->assertDatabaseHas('transaction_batches', [
        'id' => $batch->id,
        'user_id' => $user->id,
        'note' => 'Beli bulanan',
    ]);

    $this->assertDatabaseHas('transactions', [
        'transaction_batch_id' => $batch->id,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Indomie Goreng',
        'amount' => 15000.50,
        'source' => TransactionSource::BATCH->value,
        'type' => 'expense',
    ]);

    $this->assertDatabaseHas('transactions', [
        'transaction_batch_id' => $batch->id,
        'name' => 'Telur Ayam',
        'note' => 'Beli 1 Kg',
        'source' => TransactionSource::BATCH->value,
        'type' => 'expense',
    ]);
});

it('return correct batch type based on items grand total', function () {
    $user = User::factory()->create();
    $expenseCategory = Category::factory()->expense()->create(['user_id' => $user->id]);
    $incomeCategory = Category::factory()->income()->create(['user_id' => $user->id]);

    $data = new CreateBatchTransactionData(
        name: 'Belanja Indomaret',
        note: 'Beli bulanan',
        transactionAt: '2026-05-15 10:00:00',
        source: TransactionSource::BATCH,
        items: [
            new CreateBatchTransactionItemData(name: 'Indomie Goreng', amount: '5000.50', categoryId: $expenseCategory->id, type: TransactionType::EXPENSE),
            new CreateBatchTransactionItemData(name: 'Casback Belanja', amount: '10000', categoryId: $incomeCategory->id, type: TransactionType::INCOME),
        ]
    );

    $action = app(CreateBatchTransactionAction::class);
    $batch = $action->execute($user->id, $data);

    expect($batch)->toBeInstanceOf(TransactionBatch::class)
        ->and($batch->relationLoaded('transactions'))->toBeTrue()
        ->and($batch->transactions)->toHaveCount(2)
        ->and((float) $batch->amount)->toBe(4999.50)
        ->and($batch->name)->toBe('Belanja Indomaret')
        ->and($batch->note)->toBe('Beli bulanan')
        ->and($batch->type)->toBe('income');

    $this->assertDatabaseHas('transaction_batches', [
        'id' => $batch->id,
        'user_id' => $user->id,
        'note' => 'Beli bulanan',
    ]);

    $this->assertDatabaseHas('transactions', [
        'transaction_batch_id' => $batch->id,
        'user_id' => $user->id,
        'category_id' => $expenseCategory->id,
        'name' => 'Indomie Goreng',
        'amount' => 5000.50,
        'source' => TransactionSource::BATCH->value,
        'type' => 'expense',
    ]);

    $this->assertDatabaseHas('transactions', [
        'transaction_batch_id' => $batch->id,
        'name' => 'Indomie Goreng',
        'note' => null,
        'source' => TransactionSource::BATCH->value,
        'type' => 'expense',
    ]);
});

it('rolls back database if an item insertion fails', function () {
    $user = User::factory()->create();

    $invalidCategoryId = 999999;

    $data = new CreateBatchTransactionData(
        name: 'Belanja Gagal',
        note: null,
        transactionAt: '2026-05-15 10:00:00',
        source: TransactionSource::BATCH,
        items: [
            new CreateBatchTransactionItemData(name: 'Item 1', amount: '10000', categoryId: $invalidCategoryId, type: TransactionType::EXPENSE),
        ]
    );

    $action = app(CreateBatchTransactionAction::class);

    expect(fn () => $action->execute($user->id, $data))->toThrow(Exception::class);

    $this->assertDatabaseMissing('transaction_batches', [
        'name' => 'Belanja Gagal',
        'user_id' => $user->id,
    ]);
});

it('handles mixed income and expense types in a single batch', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $data = new CreateBatchTransactionData(
        name: 'Mixed Batch',
        note: null,
        transactionAt: '2026-05-15 10:00:00',
        source: TransactionSource::MANUAL,
        items: [
            new CreateBatchTransactionItemData(name: 'Beli Barang', amount: '50000', categoryId: $category->id, type: TransactionType::EXPENSE),
            new CreateBatchTransactionItemData(name: 'Cashback Promo', amount: '10000', categoryId: $category->id, type: TransactionType::INCOME),
        ]
    );

    $action = app(CreateBatchTransactionAction::class);
    $batch = $action->execute($user->id, $data);

    expect((float) $batch->amount)->toBe(40000.00);

    $this->assertDatabaseHas('transactions', [
        'transaction_batch_id' => $batch->id,
        'name' => 'Cashback Promo',
        'type' => 'income',
        'source' => 'manual',
    ]);
});
