<?php

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

test('fails when google token is missing', function () {
    $this->postJson('/api/auth/login/google', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['google_token']);
});

test('successfully authenticates with valid google token and returns sanctum token', function () {
    $googleUserMock = Mockery::mock(SocialiteUser::class);
    $googleUserMock->shouldReceive('getId')->andReturn('google-777');
    $googleUserMock->shouldReceive('getEmail')->andReturn('flutter-user@moco.com');
    $googleUserMock->shouldReceive('getName')->andReturn('Flutter Dev');
    $googleUserMock->shouldReceive('getNickname')->andReturn(null);
    $googleUserMock->shouldReceive('getAvatar')->andReturn(null);

    $providerMock = Mockery::mock(GoogleProvider::class);
    $providerMock->shouldReceive('stateless')->andReturnSelf();
    $providerMock->shouldReceive('userFromToken')
        ->with('valid-flutter-google-token')
        ->andReturn($googleUserMock);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($providerMock);

    $response = $this->postJson('/api/auth/login/google', [
        'google_token' => 'valid-flutter-google-token',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user',
                'token', // Ini token sanctum-nya
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'flutter-user@moco.com',
        'google_id' => 'google-777',
    ]);
});
