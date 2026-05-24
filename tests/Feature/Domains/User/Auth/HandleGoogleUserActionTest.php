<?php

use App\Domains\User\Actions\Auth\HandleGoogleLoginUserAction;
use App\Domains\User\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    $this->action = app(HandleGoogleLoginUserAction::class);
});

it('creates a new user and profile if google id does not exist', function () {
    $googleUserMock = Mockery::mock(SocialiteUser::class);
    $googleUserMock->shouldReceive('getId')->andReturn('google-12345');
    $googleUserMock->shouldReceive('getEmail')->andReturn('jennifer@moco.com');
    $googleUserMock->shouldReceive('getName')->andReturn('Jane');
    $googleUserMock->shouldReceive('getNickname')->andReturn('Jane');
    $googleUserMock->shouldReceive('getAvatar')->andReturn('https://avatar.url');

    $user = $this->action->execute($googleUserMock);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->email)->toBe('jennifer@moco.com')
        ->and($user->google_id)->toBe('google-12345')
        ->and($user->has_onboarded)->toBeFalse();

    $this->assertDatabaseHas('users', [
        'email' => 'jennifer@moco.com',
        'google_id' => 'google-12345',
    ]);

    $this->assertDatabaseHas('user_profiles', [
        'user_id' => $user->id,
        'display_name' => 'Jane',
        'avatar_url' => 'https://avatar.url',
    ]);
});

it('returns existing user and links google id if email matches', function () {
    $existingUser = User::factory()->create([
        'email' => 'moco-user@moco.com',
        'google_id' => null,
    ]);

    $googleUserMock = Mockery::mock(SocialiteUser::class);
    $googleUserMock->shouldReceive('getId')->andReturn('google-9999');
    $googleUserMock->shouldReceive('getEmail')->andReturn('moco-user@moco.com');
    $googleUserMock->shouldReceive('getName')->andReturn('Moco User');
    $googleUserMock->shouldReceive('getNickname')->andReturn(null);
    $googleUserMock->shouldReceive('getAvatar')->andReturn(null);

    $user = $this->action->execute($googleUserMock);

    expect($user->id)->toBe($existingUser->id)
        ->and($user->google_id)->toBe('google-9999');
});
