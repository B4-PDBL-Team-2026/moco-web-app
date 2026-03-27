<?php

use App\Models\User;
use App\Models\CustomCategory;
use Laravel\Sanctum\Sanctum;
use App\Domains\Transactions\Enums\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot store transaction', function () {
    $this->postJson('/api/transaction/transactions', [])
        ->assertUnauthorized();
});

test('authenticated user can create income transaction (Rule 19)', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = CustomCategory::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $payload = [
        'categoryId' => $category->id,
        'name' => 'Salary',
        'amount' => '1000.00',
        'type' => 'income',
        'transactionDate' => now()->toDateString(),
    ];

    $response = $this->postJson('/api/transaction/transactions', $payload);
    $response->assertCreated();
    $this->assertDatabaseHas('transactions', ['name' => 'Salary']);
});
