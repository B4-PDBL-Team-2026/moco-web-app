<?php

use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\User\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest cannot access show batch endpoint', function () {
    $this->getJson('/api/transaction/batch/1')->assertUnauthorized();
});

test('returns batch details with correct resource structure', function () {
    Sanctum::actingAs($this->user);

    $category = Category::factory()->create(['user_id' => $this->user->id]);

    $batch = TransactionBatch::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Struk Indomaret',
        'note' => 'Snacks',
        'total_amount' => 50000,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'transaction_batch_id' => $batch->id,
        'category_id' => $category->id,
        'name' => 'Susu Beruang',
        'amount' => 50000,
        'type' => 'expense',
        'source' => 'receipt_scan',
    ]);

    $response = $this->getJson("/api/transaction/batch/{$batch->id}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Batch transaction retrieved successfully.',
        ])
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'note',
                'totalAmount',
                'transactionAt',
                'items' => [
                    '*' => [
                        'id',
                        'name',
                        'amount',
                        'type',
                        'source',
                        'note',
                        'transactionAt',
                        'category' => [
                            'id',
                            'name',
                            'icon',
                        ],
                    ],
                ],
            ],
        ])
        ->assertJsonPath('data.id', $batch->id)
        ->assertJsonPath('data.name', 'Struk Indomaret')
        ->assertJsonPath('data.note', 'Snacks')
        ->assertJsonPath('data.items.0.id', $transaction->id)
        ->assertJsonPath('data.items.0.category.id', $category->id);
});

test('returns batch details with empty items if no transactions exist', function () {
    Sanctum::actingAs($this->user);

    $batch = TransactionBatch::factory()->create([
        'user_id' => $this->user->id,
        'total_amount' => 0,
    ]);

    $response = $this->getJson("/api/transaction/batch/{$batch->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $batch->id)
        ->assertJsonCount(0, 'data.items');
});

test('returns error when trying to access other users batch', function () {
    Sanctum::actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherBatch = TransactionBatch::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->getJson("/api/transaction/batch/{$otherBatch->id}");

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);
});

test('returns error when batch is not found', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/transaction/batch/99999');

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);
});
