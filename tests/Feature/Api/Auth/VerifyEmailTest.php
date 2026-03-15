<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('it can verify email successfully', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]
    );

    $response = $this->getJson($url);
    $response->dump();

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Email verified successfully.');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('it can not verify email if hashed link is invalid', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => 'invalid-hash']
    );

    $response = $this->getJson($url);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Verification link is invalid.');
});
