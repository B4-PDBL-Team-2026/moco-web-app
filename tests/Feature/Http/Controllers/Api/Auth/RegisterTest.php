<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it can register a user via api', function () {
    // Act
    $response = $this->postJson('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Assert
    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'token',
                'requires_onboarding',
            ],
        ])
        ->assertJsonPath('data.requires_onboarding', true);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'name' => 'John Doe',
    ]);
});

test('it validates registration request', function () {
    // Act
    $response = $this->postJson('/api/auth/register', []);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password'], 'data');
});

test('it cannot register with existing email', function () {
    // Arrange
    User::factory()->create([
        'email' => 'duplicate@example.com',
    ]);

    // Act
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'duplicate@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email'], 'data');
});

test('it requires password confirmation', function () {
    // Act
    $response = $this->postJson('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'wrong-password',
    ]);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password'], 'data');
});
