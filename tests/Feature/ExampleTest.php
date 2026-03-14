<?php

use App\Models\User;

test('returns a successful response', function () {
    // Buat user dummy dan login-kan
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
});
