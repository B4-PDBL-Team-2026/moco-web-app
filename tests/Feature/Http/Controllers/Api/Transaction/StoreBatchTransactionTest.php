<?php

use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;

it('denies unauthenticated users', function () {
    $this->postJson('/api/transaction/batch', [])->assertUnauthorized();
});

it('validates required payload for batch transaction', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/transaction/batch', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'transactionAt', 'items']);
});

it('validates nested items array payload correctly', function () {
    $user = User::factory()->create();

    $payload = [
        'name' => 'Belanja Validasi',
        'transactionAt' => now()->toDateTimeString(),
        'items' => [
            [
                'name' => '',
                'amount' => -5000,
                'categoryId' => 99999,
            ],
        ],
    ];

    $response = $this->actingAs($user)->postJson('/api/transaction/batch', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'items.0.name',
            'items.0.amount',
            'items.0.categoryId',
            'items.0.type',
        ]);
});

it('validates source enum correctly', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $payload = [
        'name' => 'Test Source',
        'transactionAt' => now()->toDateTimeString(),
        'source' => 'invalid_source_type',
        'items' => [
            [
                'name' => 'Item',
                'amount' => 10000,
                'categoryId' => $category->id,
                'type' => TransactionType::EXPENSE->value,
            ],
        ],
    ];

    $response = $this->actingAs($user)->postJson('/api/transaction/batch', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['source']);
});

it('successfully stores batch transaction and returns correct resource format', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $payload = [
        'name' => 'Belanja Supermarket',
        'transactionAt' => '2026-04-15 12:00:00',
        'note' => 'Catatan batch',
        'source' => TransactionSource::BATCH->value,
        'items' => [
            [
                'name' => 'Sabun Mandi',
                'amount' => 20000,
                'categoryId' => $category->id,
                'type' => TransactionType::EXPENSE->value,
            ],
            [
                'name' => 'Shampoo',
                'amount' => 35000,
                'categoryId' => $category->id,
                'type' => TransactionType::EXPENSE->value,
                'note' => 'Beli yang botol gede',
            ],
        ],
    ];

    $response = $this->actingAs($user)->postJson('/api/transaction/batch', $payload);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Batch transactions saved successfully.',
        ])
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'totalAmount',
                'transactionAt',
                'note',
                'type',
                'items' => [
                    '*' => [
                        'id', 'name', 'amount', 'type', 'source', 'note', 'transactionAt',
                        'category' => ['id', 'name', 'icon'],
                    ],
                ],
            ],
        ])
        ->assertJsonPath('data.name', 'Belanja Supermarket')
        ->assertJsonPath('data.note', 'Catatan batch')
        ->assertJsonPath('data.totalAmount', '55000.00')
        ->assertJsonPath('data.type', 'expense')
        ->assertJsonCount(2, 'data.items')
        ->assertJsonPath('data.items.0.name', 'Sabun Mandi')
        ->assertJsonPath('data.items.0.type', 'expense')
        ->assertJsonPath('data.items.0.source', TransactionSource::BATCH->value);
});

it('defaults source to manual if not provided', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $payload = [
        'name' => 'Belanja Default Source',
        'transactionAt' => '2026-04-15 12:00:00',
        'items' => [
            [
                'name' => 'Item 1',
                'amount' => 10000,
                'categoryId' => $category->id,
                'type' => TransactionType::EXPENSE->value,
            ],
        ],
    ];

    $response = $this->actingAs($user)->postJson('/api/transaction/batch', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.items.0.source', 'manual');
});
