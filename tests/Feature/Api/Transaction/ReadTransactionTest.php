<?php

use App\Models\User;
use App\Models\Transaction;

test('it can get transactions list', function () {

    $user = User::factory()->create();

    Transaction::factory()->count(3)->create([
        'user_id' => $user->id
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/transactions');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'amount',
                    'type',
                    'note',
                    'user_id',
                    'category_id'
                ]
            ]
    ]);
});