<?php

use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Actions\CreateBatchTransactionAction;
use App\Domains\Transaction\DTOs\CreateBatchTransactionData;
use App\Domains\Transaction\DTOs\CreateBatchTransactionItemData;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\User\Models\User;

it('successfully stores a batch and its items with correct total amount', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $data = new CreateBatchTransactionData(
        name: 'Belanja Indomaret',
        type: TransactionType::EXPENSE,
        transactionAt: '2026-05-15 10:00:00',
        items: [
            new CreateBatchTransactionItemData(name: 'Indomie Goreng', amount: '15000.50', categoryId: $category->id),
            new CreateBatchTransactionItemData(name: 'Telur Ayam', amount: '25000.00', categoryId: $category->id, note: 'Beli 1 Kg'),
        ]
    );

    $action = app(CreateBatchTransactionAction::class);
    $batch = $action->execute($user->id, $data);

    // Assert return type and loaded relation
    expect($batch)->toBeInstanceOf(TransactionBatch::class)
        ->and($batch->relationLoaded('transactions'))->toBeTrue()
        ->and($batch->transactions)->toHaveCount(2)
        ->and((float) $batch->total_amount)->toBe(40000.50)
        ->and($batch->name)->toBe('Belanja Indomaret');

    // Assert calculated total is correct (15000.50 + 25000.00 = 40000.50)
    // Note: Cast to float for exact Pest comparison if your DB returns decimal as string
    $this->assertDatabaseHas('transaction_batches', [
        'id' => $batch->id,
        'user_id' => $user->id,
        'total_amount' => 40000.50,
    ]);

    // Assert Child Database Records (Items)
    $this->assertDatabaseHas('transactions', [
        'transaction_batch_id' => $batch->id,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Indomie Goreng',
        'amount' => 15000.50,
        'source' => 'receipt_scan',
    ]);

    $this->assertDatabaseHas('transactions', [
        'transaction_batch_id' => $batch->id,
        'name' => 'Telur Ayam',
        'note' => 'Beli 1 Kg',
        'source' => 'receipt_scan',
    ]);
});

it('rolls back database if an item insertion fails', function () {
    $user = User::factory()->create();

    $invalidCategoryId = 999999;

    $data = new CreateBatchTransactionData(
        name: 'Belanja Gagal',
        type: TransactionType::EXPENSE,
        transactionAt: '2026-05-15 10:00:00',
        items: [
            new CreateBatchTransactionItemData(name: 'Item 1', amount: '10000', categoryId: $invalidCategoryId),
        ]
    );

    $action = app(CreateBatchTransactionAction::class);

    expect(fn () => $action->execute($user->id, $data))->toThrow(Exception::class);

    $this->assertDatabaseMissing('transaction_batches', [
        'name' => 'Belanja Gagal',
        'user_id' => $user->id,
    ]);
});
