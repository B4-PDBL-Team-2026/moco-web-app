<?php

use App\Models\User;

test('it can reset password successfully', function () {
    $user = User::factory()->create([
        'email' => 'test@moco.com',
        'password' => Hash::make('password_lama'),
    ]);

    $token = Password::broker()->createToken($user);

    $response = $this->postJson('/api/auth/password/reset', [
        'email' => 'test@moco.com',
        'token' => $token,
        'password' => 'PasswordBaru123!',
        'password_confirmation' => 'PasswordBaru123!',
    ]);

    $response->assertStatus(200)->assertJsonPath('success', true);

    expect(Hash::check('PasswordBaru123!', $user->fresh()->password))->toBeTrue();
});

test('it can not reset password if token is invalid', function () {
    User::factory()->create(['email' => 'test@moco.com']);

    $response = $this->postJson('/api/auth/password/reset', [
        'email' => 'test@moco.com',
        'token' => 'token-palsu-ngasal',
        'password' => 'PasswordBaru123!',
        'password_confirmation' => 'PasswordBaru123!',
    ]);

    $response->assertStatus(422)->assertJsonPath('success', false);
    $response->assertJsonPath('message', 'This password reset token is invalid.');
});

test('it can not reset password if confirmation field not match', function () {
    $response = $this->postJson('/api/auth/password/reset', [
        'email' => 'test@moco.com',
        'token' => 'token-apa-aja',
        'password' => 'PasswordBaru123!',
        'password_confirmation' => 'BedaPasswordMase!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('it can not reset password if password does not meet criteria', function () {
    $user = User::factory()->create([
        'email' => 'test@moco.com',
        'password' => Hash::make('password_lama'),
    ]);

    $token = Password::broker()->createToken($user);

    $response = $this->postJson('/api/auth/password/reset', [
        'email' => 'test@moco.com',
        'token' => $token,
        'password' => 'lemah',
        'password_confirmation' => 'lemah',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
