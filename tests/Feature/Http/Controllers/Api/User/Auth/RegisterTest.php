<?php

use App\Domains\User\Models\User;

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
                'requiresOnboarding',
            ],
        ])
        ->assertJsonPath('data.requiresOnboarding', true);

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
        ->assertJsonValidationErrors(['name', 'email', 'password']);
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
        ->assertJsonValidationErrors(['email']);
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
        ->assertJsonValidationErrors(['password']);
});
