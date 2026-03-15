<?php

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

test('user can create transaction', function () {

    $user = User::factory()->create();
    $category = Category::factory()->create();

    $payload = [
        'name' => 'Beli makan siang',
        'amount' => 25000,
        'type' => 'expense',
        'category_id' => $category->id,
        'note' => 'Test transaksi'
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/transactions', $payload);

    $response->assertStatus(201);
});