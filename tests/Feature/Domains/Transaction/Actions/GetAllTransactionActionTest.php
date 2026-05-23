<?php

use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Actions\GetAllTransactionAction;
use App\Domains\Transaction\DTOs\FilterTransactionData;
use App\Domains\Transaction\Enums\TransactionFeedType;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

// HELPERS

if (! function_exists('txFilters')) {
    function txFilters(array $overrides = []): FilterTransactionData
    {
        return new FilterTransactionData(
            month: $overrides['month'] ?? null,
            year: $overrides['year'] ?? null,
            search: $overrides['search'] ?? null,
            categoryId: $overrides['categoryId'] ?? null,
            perPage: $overrides['perPage'] ?? 10,
            transactionType: isset($overrides['transactionType']) ? TransactionType::tryFrom($overrides['transactionType']) : null,
            transactionFeedType: isset($overrides['transactionFeedType']) ? TransactionFeedType::tryFrom($overrides['transactionFeedType']) : null,
        );
    }
}

if (! function_exists('makeTx')) {
    function makeTx(User $user, array $overrides = [], ?Category $category = null): Transaction
    {
        $cat = $category ?? Category::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        return Transaction::factory()->create(array_merge([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'transaction_at' => '2026-03-15 10:00:00',
            'transaction_batch_id' => null,
        ], $overrides));
    }
}

if (! function_exists('makeBatchTx')) {
    function makeBatchTx(User $user, array $overrides = [], array $itemCategories = [], string $itemType = 'expense'): TransactionBatch
    {
        $batch = TransactionBatch::factory()->create(array_merge([
            'user_id' => $user->id,
            'name' => 'Struk Belanja',
            'transaction_at' => '2026-03-15 10:00:00',
        ], $overrides));

        foreach ($itemCategories as $catId) {
            Transaction::factory()->create([
                'user_id' => $user->id,
                'transaction_batch_id' => $batch->id,
                'category_id' => $catId,
                'type' => $itemType,
                'amount' => 10000,
                'transaction_at' => $batch->transaction_at,
            ]);
        }

        return $batch;
    }
}

// SETUP

beforeEach(function () {
    $this->action = app(GetAllTransactionAction::class);
    $this->user = User::factory()->create();
});

// HAPPY PATHS

it('returns a LengthAwarePaginator containing both single and batch transactions', function () {
    makeTx($this->user, ['name' => 'Single Tx']);
    makeBatchTx($this->user, ['name' => 'Batch Tx']);

    $result = $this->action->execute($this->user->id, txFilters());

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(2);

    $feedTypes = collect($result->items())->pluck('feed_type')->toArray();
    expect($feedTypes)->toContain('single', 'batch');
});

it('returns transactions only for the given user', function () {
    makeTx($this->user);
    makeBatchTx($this->user);

    $otherUser = User::factory()->create();
    makeTx($otherUser);
    makeBatchTx($otherUser);

    $result = $this->action->execute($this->user->id, txFilters());

    expect($result->total())->toBe(2);
});

// FILTER PATHS

it('filters both feeds by month', function () {
    makeTx($this->user, ['transaction_at' => '2026-03-10 10:00:00']);
    makeBatchTx($this->user, ['transaction_at' => '2026-02-10 10:00:00']);

    $result = $this->action->execute($this->user->id, txFilters(['month' => 3]));

    expect($result->total())->toBe(1)
        ->and(Carbon::parse($result->items()[0]->transaction_at)->format('m'))->toBe('03')
        ->and($result->items()[0]->feed_type)->toBe('single');
});

it('filters both feeds by year', function () {
    makeTx($this->user, ['transaction_at' => '2025-01-10 10:00:00']);
    makeBatchTx($this->user, ['transaction_at' => '2026-01-10 10:00:00']);

    $result = $this->action->execute($this->user->id, txFilters(['year' => 2026]));

    expect($result->total())->toBe(1)
        ->and(Carbon::parse($result->items()[0]->transaction_at)->format('Y'))->toBe('2026')
        ->and($result->items()[0]->feed_type)->toBe('batch');
});

it('filters both feeds by partial search name', function () {
    makeTx($this->user, ['name' => 'Beli Kuota Telkomsel']);
    makeBatchTx($this->user, ['name' => 'Belanja Superindo']);
    makeTx($this->user, ['name' => 'Makan Siang']);

    $result = $this->action->execute($this->user->id, txFilters(['search' => 'Belanja']));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Belanja Superindo');
});

it('filters single transactions by categoryId', function () {
    $catA = Category::factory()->create(['user_id' => $this->user->id]);
    $catB = Category::factory()->create(['user_id' => $this->user->id]);

    makeTx($this->user, [], $catA);
    makeTx($this->user, [], $catB);

    $result = $this->action->execute($this->user->id, txFilters(['categoryId' => $catA->id]));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->category_id)->toBe($catA->id);
});

it('filters batch transactions if ANY of its items match the categoryId', function () {
    $targetCat = Category::factory()->create(['user_id' => $this->user->id]);
    $otherCat = Category::factory()->create(['user_id' => $this->user->id]);

    makeBatchTx($this->user, ['name' => 'Batch Target'], [$otherCat->id, $targetCat->id]);
    makeBatchTx($this->user, ['name' => 'Batch Miss'], [$otherCat->id, $otherCat->id]);

    $result = $this->action->execute($this->user->id, txFilters(['categoryId' => $targetCat->id]));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Batch Target');
});

it('filters both feeds by transactionType', function () {
    $expenseCat = Category::factory()->create(['user_id' => $this->user->id]);
    $incomeCat = Category::factory()->create(['user_id' => $this->user->id]);

    makeTx($this->user, ['type' => 'expense', 'name' => 'Expense Tx']);
    makeTx($this->user, ['type' => 'income', 'name' => 'Income Tx']);

    makeBatchTx($this->user, ['name' => 'Expense Batch'], [$expenseCat->id], 'expense');
    makeBatchTx($this->user, ['name' => 'Income Batch'], [$incomeCat->id], 'income');

    $result = $this->action->execute($this->user->id, txFilters(['transactionType' => 'expense']));

    expect($result->total())->toBe(2);
    $names = collect($result->items())->pluck('name')->toArray();
    expect($names)->toContain('Expense Tx', 'Expense Batch')
        ->not->toContain('Income Tx', 'Income Batch');
});

it('filters feeds by transactionFeedType single', function () {
    makeTx($this->user, ['name' => 'Single Record']);
    makeBatchTx($this->user, ['name' => 'Batch Record']);

    $result = $this->action->execute($this->user->id, txFilters(['transactionFeedType' => 'single']));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Single Record')
        ->and($result->items()[0]->feed_type)->toBe('single');
});

it('filters feeds by transactionFeedType batch', function () {
    makeTx($this->user, ['name' => 'Single Record']);
    makeBatchTx($this->user, ['name' => 'Batch Record']);

    $result = $this->action->execute($this->user->id, txFilters(['transactionFeedType' => 'batch']));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Batch Record')
        ->and($result->items()[0]->feed_type)->toBe('batch');
});

// EDGE CASES & SORTING

it('returns feeds sorted by transaction_at descending across both tables', function () {
    $oldest = makeTx($this->user, ['transaction_at' => '2026-03-01 10:00:00']);
    $newest = makeBatchTx($this->user, ['transaction_at' => '2026-03-03 10:00:00']);

    $result = $this->action->execute($this->user->id, txFilters());

    expect($result->items()[0]->id)->toBe($newest->id)
        ->and($result->items()[0]->feed_type)->toBe('batch')
        ->and($result->items()[1]->id)->toBe($oldest->id);
});

it('excludes soft-deleted records from both single and batch transactions', function () {
    $tx = makeTx($this->user, ['name' => 'Deleted Tx']);
    $tx->delete();

    $batch = makeBatchTx($this->user, ['name' => 'Deleted Batch']);
    $batch->delete();

    $result = $this->action->execute($this->user->id, txFilters());

    expect($result->total())->toBe(0);
});

it('respects the perPage value for union queries', function () {
    for ($i = 0; $i < 10; $i++) {
        makeTx($this->user);
    }
    for ($i = 0; $i < 5; $i++) {
        makeBatchTx($this->user);
    }

    $result = $this->action->execute($this->user->id, txFilters(['perPage' => 7]));

    expect($result->total())->toBe(15)
        ->and($result->items())->toHaveCount(7)
        ->and($result->lastPage())->toBe(3);
});
