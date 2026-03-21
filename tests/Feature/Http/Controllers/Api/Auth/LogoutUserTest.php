<?php

use App\Models\User;

test('authenticated user can logout from current device', function () {
    $user = User::factory()->create();

    $userToken = $user->createToken('iphone');
    $plainTextToken = $userToken->plainTextToken;
    $tokenId = $userToken->accessToken->id;

    $response = $this
        ->withHeader('Authorization', 'Bearer '.$plainTextToken)
        ->deleteJson('/api/auth/logout');

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Successfully logged out.');

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $tokenId,
    ]);
});

test('logout only revokes the current access token', function () {
    $user = User::factory()->create();

    $currentPlainTextToken = $user->createToken('current-device')->plainTextToken;
    $otherPlainTextToken = $user->createToken('other-device')->plainTextToken;
    $currentTokenId = explode('|', $currentPlainTextToken)[0];
    $otherTokenId = explode('|', $otherPlainTextToken)[0];

    $response = $this
        ->withHeader('Authorization', 'Bearer '.$currentPlainTextToken)
        ->deleteJson('/api/auth/logout');

    $response->assertOk();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $currentTokenId,
    ]);

    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $otherTokenId,
    ]);
});

test('guest cannot access logout endpoint', function () {
    $response = $this->deleteJson('/api/auth/logout');

    $response->assertUnauthorized();
});
