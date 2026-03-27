<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access index', function () {
    $this->getJson('/api/transaction/transactions')
        ->assertUnauthorized();
});

test('index endpoint validates month', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/transaction/transactions?month=13')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['month'], 'data');
});
