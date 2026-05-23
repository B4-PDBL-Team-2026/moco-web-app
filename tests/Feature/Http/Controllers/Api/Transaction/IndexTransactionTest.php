<?php

use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Enums\TransactionFeedType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\User\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

// SETUP HELPERS

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->endpoint = '/api/transaction';
});

// AUTH & VALIDATION TESTS

test('guest cannot access index endpoint', function () {
    $this->getJson($this->endpoint)->assertUnauthorized();
});

test('validates month parameter', function () {
    Sanctum::actingAs($this->user);

    $this->getJson("{$this->endpoint}?month=13")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['month']);

    $this->getJson("{$this->endpoint}?month=0")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['month']);
});

test('validates year parameter', function () {
    Sanctum::actingAs($this->user);

    $this->getJson("{$this->endpoint}?year=202")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['year']);
});

test('validates categoryId parameter', function () {
    Sanctum::actingAs($this->user);

    $this->getJson("{$this->endpoint}?categoryId=999999")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['categoryId']);
});

test('validates perPage parameter', function () {
    Sanctum::actingAs($this->user);

    $this->getJson("{$this->endpoint}?perPage=101")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['perPage']);

    $this->getJson("{$this->endpoint}?perPage=0")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['perPage']);
});

test('validates transactionType parameter', function () {
    Sanctum::actingAs($this->user);

    $this->getJson("{$this->endpoint}?transactionType=invalid_type")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['transactionType']);
});

test('validates transactionFeedType parameter', function () {
    Sanctum::actingAs($this->user);

    $this->getJson("{$this->endpoint}?transactionFeedType=invalid_feed")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['transactionFeedType']);
});

// STRUCTURAL & HAPPY PATH TESTS

test('returns paginated activity feed with correct mixed resource structure', function () {
    Sanctum::actingAs($this->user);

    $category = Category::factory()->create(['user_id' => $this->user->id]);

    $singleTx = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'name' => 'Beli Cilok',
        'amount' => 15000,
        'type' => 'expense',
        'source' => 'manual',
        'note' => 'Cilok mang oleh',
        'transaction_at' => Carbon::now()->subHours(2)->toDateTimeString(),
    ]);

    $batchTx = TransactionBatch::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Belanja Superindo',
        'transaction_at' => Carbon::now()->toDateTimeString(),
    ]);

    Transaction::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'name' => 'Beli Cilok',
        'amount' => 150000,
        'type' => 'expense',
        'source' => 'manual',
        'note' => 'Cilok mang oleh',
        'transaction_at' => Carbon::now()->subHours(2)->toDateTimeString(),
        'transaction_batch_id' => $batchTx->id,
    ]);

    $response = $this->getJson($this->endpoint);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'amount',
                    'type',
                    'note',
                    'source',
                    'transactionAt',
                    'feedType',
                    'category',
                ],
            ],
            'meta' => [
                'currentPage',
                'lastPage',
                'perPage',
                'total',
            ],
        ])
        ->assertJsonCount(2, 'data');

    $response->assertJsonPath('data.0.id', $batchTx->id)
        ->assertJsonPath('data.0.feedType', 'batch')
        ->assertJsonPath('data.0.name', 'Belanja Superindo')
        ->assertJsonPath('data.0.amount', 150000)
        ->assertJsonPath('data.0.type', 'expense')
        ->assertJsonPath('data.0.note', null)
        ->assertJsonPath('data.0.source', TransactionFeedType::BATCH->value)
        ->assertJsonPath('data.0.category', null);

    $response->assertJsonPath('data.1.id', $singleTx->id)
        ->assertJsonPath('data.1.feedType', 'single')
        ->assertJsonPath('data.1.name', 'Beli Cilok')
        ->assertJsonPath('data.1.type', 'expense')
        ->assertJsonPath('data.1.source', 'manual')
        ->assertJsonPath('data.1.note', 'Cilok mang oleh')
        ->assertJsonPath('data.1.category.id', $category->id)
        ->assertJsonPath('data.1.category.name', $category->name);
});

test('applies search filter via query params correctly', function () {
    Sanctum::actingAs($this->user);

    Transaction::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Bensin Pertamax',
    ]);

    TransactionBatch::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Tagihan Listrik',
    ]);

    $response = $this->getJson("{$this->endpoint}?search=tagihan");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Tagihan Listrik')
        ->assertJsonPath('data.0.feedType', 'batch');
});

test('applies transactionType filter via query params correctly', function () {
    Sanctum::actingAs($this->user);

    Transaction::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Expense Tx',
        'type' => 'expense',
    ]);

    Transaction::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Income Tx',
        'type' => 'income',
    ]);

    $response = $this->getJson("{$this->endpoint}?transactionType=income");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Income Tx')
        ->assertJsonPath('data.0.type', 'income');
});

test('applies transactionFeedType filter via query params correctly', function () {
    Sanctum::actingAs($this->user);

    Transaction::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Single Record',
    ]);

    TransactionBatch::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Batch Record',
    ]);

    $response = $this->getJson("{$this->endpoint}?transactionFeedType=batch");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Batch Record')
        ->assertJsonPath('data.0.feedType', 'batch');
});

test('returns empty data when no transactions match the filters', function () {
    Sanctum::actingAs($this->user);

    Transaction::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Bensin Pertamax',
        'transaction_at' => '2026-05-15 10:00:00',
    ]);

    $response = $this->getJson("{$this->endpoint}?month=12&year=2026");

    $response->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});
