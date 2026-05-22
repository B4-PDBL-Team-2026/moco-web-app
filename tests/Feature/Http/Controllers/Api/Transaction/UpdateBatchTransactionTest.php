<?php

use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\User\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    [$this->user, $this->category] = setupUserWithBudget(['current_balance' => '5000.00']);
    $this->batch = TransactionBatch::factory()->create(['user_id' => $this->user->id]);
});

test('guest cannot update batch transaction', function () {
    $this->putJson("/api/transactions/batch/{$this->batch->id}", [])
        ->assertNotFound();
});

test('user cannot update another users batch transaction', function () {
    $otherUser = User::factory()->create();
    $otherBatch = TransactionBatch::factory()->create(['user_id' => $otherUser->id]);

    Sanctum::actingAs($this->user);

    $this->putJson("/api/transactions/batch/{$otherBatch->id}", [
        'name' => 'Hacked Name',
        'transactionAt' => now()->toIso8601String(),
        'items' => [
            [
                'name' => 'Item',
                'amount' => 100,
                'categoryId' => $this->category->id,
                'type' => 'expense',
            ],
        ],
    ])->assertStatus(404);
});

test('fails validation when payload is missing required fields', function () {
    Sanctum::actingAs($this->user);

    $this->putJson("/api/transaction/batch/{$this->batch->id}", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'transactionAt', 'items']);
});

test('fails validation when items array is empty or malformed', function () {
    Sanctum::actingAs($this->user);

    $payload = [
        'name' => 'Invalid Items',
        'transactionAt' => now()->toIso8601String(),
        'items' => [
            [
                'name' => '',
                'amount' => -10,
                'categoryId' => 9999,
                'type' => 'invalid_type',
            ],
        ],
    ];

    $this->putJson("/api/transaction/batch/{$this->batch->id}", $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'items.0.name',
            'items.0.amount',
            'items.0.categoryId',
            'items.0.type',
        ]);
});

test('fails validation when transaction date is in the future', function () {
    Sanctum::actingAs($this->user);

    $this->travelTo(CarbonImmutable::parse('2026-05-22 10:00:00', 'UTC'));

    $payload = [
        'name' => 'Future Batch',
        'transactionAt' => '2026-05-22T15:00:00Z',
        'items' => [
            [
                'name' => 'Item',
                'amount' => 100,
                'categoryId' => $this->category->id,
                'type' => 'expense',
            ],
        ],
    ];

    $this->putJson("/api/transaction/batch/{$this->batch->id}", $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['transactionAt']);
});

test('fails when update causes insufficient balance', function () {
    [$poorUser, $category] = setupUserWithBudget(['current_balance' => '50.00']);
    $batch = TransactionBatch::factory()->create(['user_id' => $poorUser->id]);

    Sanctum::actingAs($poorUser);

    $payload = [
        'name' => 'Exceeding Budget',
        'transactionAt' => now()->toIso8601String(),
        'items' => [
            [
                'name' => 'Too Expensive',
                'amount' => 999999,
                'categoryId' => $category->id,
                'type' => 'expense',
            ],
        ],
    ];

    $this->putJson("/api/transaction/batch/{$batch->id}", $payload)
        ->assertStatus(422);
});

test('successfully updates batch and returns correct resource structure', function () {
    Transaction::factory()->create([
        'transaction_batch_id' => $this->batch->id,
        'user_id' => $this->user->id,
        'amount' => 50.00,
        'type' => 'expense',
    ]);

    Sanctum::actingAs($this->user);

    $payload = [
        'name' => 'Updated Weekend Groceries',
        'transactionAt' => '2026-03-20T12:00:00Z',
        'note' => 'Bought at farmer market',
        'source' => TransactionSource::BATCH->value,
        'items' => [
            [
                'name' => 'Apples',
                'amount' => 30.00,
                'categoryId' => $this->category->id,
                'type' => 'expense',
            ],
            [
                'name' => 'Discount',
                'amount' => 5.00,
                'categoryId' => $this->category->id,
                'type' => 'income',
                'note' => 'Coupon applied',
            ],
        ],
    ];

    $response = $this->putJson("/api/transaction/batch/{$this->batch->id}", $payload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Batch transactions updated successfully.',
        ])
        ->assertJsonPath('data.name', 'Updated Weekend Groceries')
        ->assertJsonPath('data.note', 'Bought at farmer market')
        ->assertJsonPath('data.totalAmount', '25.00')
        ->assertJsonPath('data.type', 'expense')
        ->assertJsonCount(2, 'data.items')
        ->assertJsonPath('data.items.0.name', 'Apples')
        ->assertJsonPath('data.items.1.name', 'Discount');

    $this->assertDatabaseHas('transaction_batches', [
        'id' => $this->batch->id,
        'name' => 'Updated Weekend Groceries',
        'total_amount' => 25.00,
    ]);

    $this->assertDatabaseHas('transactions', [
        'transaction_batch_id' => $this->batch->id,
        'name' => 'Apples',
        'amount' => 30.00,
        'source' => TransactionSource::BATCH->value,
    ]);
});
