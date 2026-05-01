<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it can login a user and return token via api', function () {
    // Arrange
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('Password123!'),
    ]);

    // Act
    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
            ],
        ])
        ->assertJsonPath('success', true);
});

test('it rejects login with wrong password', function () {
    // Arrange
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('Password123!'),
    ]);

    // Act
    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'WrongPassword123!',
    ]);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('it rejects login with unregistered email', function () {
    // Act
    $response = $this->postJson('/api/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'Password123!',
    ]);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('it validates empty login request', function () {
    // Act
    $response = $this->postJson('/api/auth/login', []);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('it validates email format on login', function () {
    // Act
    $response = $this->postJson('/api/auth/login', [
        'email' => 'not-an-email',
        'password' => 'Password123!',
    ]);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
