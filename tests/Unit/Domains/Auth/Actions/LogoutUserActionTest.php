<?php

use App\Domains\User\Actions\Auth\LogoutUserAction;
use App\Domains\User\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('deletes the current access token', function () {
    $user = User::factory()->create();

    $plainTextToken = $user->createToken('mobile')->plainTextToken;
    $tokenId = explode('|', $plainTextToken)[0];

    /** @var PersonalAccessToken $token */
    $token = PersonalAccessToken::query()->findOrFail($tokenId);

    $user->withAccessToken($token);

    app(LogoutUserAction::class)->execute($user);

    expect(PersonalAccessToken::query()->find($tokenId))->toBeNull();
});

it('does not delete other user tokens', function () {
    $user = User::factory()->create();

    $currentPlainTextToken = $user->createToken('current-device')->plainTextToken;
    $otherPlainTextToken = $user->createToken('other-device')->plainTextToken;

    $currentTokenId = explode('|', $currentPlainTextToken)[0];
    $otherTokenId = explode('|', $otherPlainTextToken)[0];

    /** @var PersonalAccessToken $currentToken */
    $currentToken = PersonalAccessToken::query()->findOrFail($currentTokenId);

    $user->withAccessToken($currentToken);

    app(LogoutUserAction::class)->execute($user);

    expect(PersonalAccessToken::query()->find($currentTokenId))->toBeNull()
        ->and(PersonalAccessToken::query()->find($otherTokenId))->not->toBeNull();
});

it('does nothing when user has no current access token', function () {
    $user = User::factory()->create();

    app(LogoutUserAction::class)->execute($user);

    expect(true)->toBeTrue();
});
