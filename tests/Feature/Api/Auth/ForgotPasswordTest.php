<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it should send forgot forget password link', function () {
    $user = User::factory()->create(['email' => 'test@moco.com']);

    $response = $this->postJson('/api/auth/password/email', [
        'email' => 'test@moco.com',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'data', 'message']);
});

test('it cannot send forget password link if email not found', function () {
    $response = $this->postJson('/api/auth/password/email', [
        'email' => 'ngasal@email.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});
