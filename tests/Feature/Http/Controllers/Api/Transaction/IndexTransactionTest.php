<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guest cannot access index', function () {
    $this->getJson('/api/transaction')->assertUnauthorized();
});

test('index endpoint validates month', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/transaction?month=13')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['month']);
});
