<?php

use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;

it('denies unauthenticated users', function () {
    $this->postJson('/api/transaction', [])->assertUnauthorized();
});

it('validates required payload for transaction', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/transaction', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['categoryId', 'name', 'amount', 'type', 'transactionAt']);
});

it('validates correct enum type', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $payload = [
        'categoryId' => $category->id,
        'name' => 'Makan Siang',
        'amount' => 50000.00,
        'type' => 'invalid_type',
        'transactionAt' => '2026-05-15 12:00:00',
    ];

    $response = $this->actingAs($user)->postJson('/api/transaction', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

it('successfully stores transaction and returns correct resource format', function () {
    [$user] = setupUserWithBudget();
    $category = Category::factory()->expense()->create(['user_id' => $user->id]);

    $payload = [
        'categoryId' => $category->id,
        'name' => 'Makan Siang',
        'amount' => 500.00,
        'type' => TransactionType::EXPENSE->value,
        'note' => 'Makan bareng tim',
        'transactionAt' => '2026-05-15 12:00:00',
    ];

    $response = $this->actingAs($user)->postJson('/api/transaction', $payload)->dump();

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Transaction created successfully.',
        ])
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'amount',
                'type',
                'note',
                'transactionAt',
                'category' => ['id', 'name', 'icon'],
            ],
        ])
        ->assertJsonPath('data.name', 'Makan Siang')
        ->assertJsonPath('data.amount', '500.00')
        ->assertJsonPath('data.type', 'expense');
});
