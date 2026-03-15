<?php

use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

test('it can update transaction', function () {

    $user = User::factory()->create();
    $category = Category::factory()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id
    ]);

    $payload = [
        'category_id' => $category->id,
        'name' => 'Updated Transaction',
        'amount' => 50000,
        'type' => 'income',
        'transaction_date' => now()->toDateString(),
        'note' => 'updated note'
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/transactions/{$transaction->id}", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Updated Transaction');
});