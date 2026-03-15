<?php

use App\Models\User;
use App\Models\Transaction;

test('user can delete transaction', function () {

    $user = User::factory()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/transactions/{$transaction->id}");

    $response->assertStatus(200);
});