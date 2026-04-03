<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

// Helpers
function signedVerifyUrl(User $user, ?string $hash = null): string
{
    return URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => $hash ?? sha1($user->getEmailForVerification()),
        ]
    );
}

// Valid link

test('it can verify email successfully', function () {
    $user = User::factory()->unverified()->create();

    $this->get(signedVerifyUrl($user))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Auth/EmailVerification')
                ->where('status', 'success')
                ->where('message', 'Email verified successfully.')
        );

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('it renders the EmailVerification Inertia component on success', function () {
    $user = User::factory()->unverified()->create();

    $this->get(signedVerifyUrl($user))
        ->assertInertia(fn ($page) => $page->component('Auth/EmailVerification'));
});

test('it marks the email as verified in the database', function () {
    $user = User::factory()->unverified()->create();

    $this->get(signedVerifyUrl($user));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

// Already verified

test('it returns already-verified message when link is clicked again', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->get(signedVerifyUrl($user))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Auth/EmailVerification')
                ->where('status', 'success')
                ->where('message', 'Email is already verified.')
        );
});

// Invalid hash

test('it can not verify email if hashed link is invalid', function () {
    $user = User::factory()->unverified()->create();

    $this->get(signedVerifyUrl($user, 'invalid-hash'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Auth/EmailVerification')
                ->where('status', 'error')
                ->where('message', 'Verification link is invalid.')
        );
});

test('it does not mark email as verified when hash is invalid', function () {
    $user = User::factory()->unverified()->create();

    $this->get(signedVerifyUrl($user, 'invalid-hash'));

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

// Tampered / unsigned URL

test('it returns 403 when the URL signature is missing entirely', function () {
    $user = User::factory()->unverified()->create();

    // No signature at all — the signed middleware rejects before controller runs
    $this->get("/auth/verify-email/$user->id/somehash")
        ->assertForbidden();
});
