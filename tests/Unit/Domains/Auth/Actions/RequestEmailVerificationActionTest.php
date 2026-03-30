<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

test('unauthenticated request returns 401', function () {
    $this->getJson('/api/auth/verify-email/request')
        ->assertUnauthorized();
});

test('returns 200 and sends verification email to unverified user', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/auth/verify-email/request')
        ->assertOk()
        ->assertJsonPath('message', 'Email verification link sent on email.');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('returns success status for an unverified user', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/auth/verify-email/request')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('sends exactly one notification per request', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/auth/verify-email/request')->assertOk();

    Notification::assertSentToTimes($user, VerifyEmail::class, 1);
});

test('can resend verification email by calling the endpoint again', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/auth/verify-email/request')->assertOk();
    $this->getJson('/api/auth/verify-email/request')->assertOk();

    Notification::assertSentToTimes($user, VerifyEmail::class, 2);
});

test('returns 200 with already-verified message when email is already verified', function () {
    Notification::fake();

    $user = User::factory()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($user);

    $this->getJson('/api/auth/verify-email/request')
        ->assertOk()
        ->assertJsonPath('message', 'Email is already verified.');
});

test('returns success status even when email is already verified', function () {
    Notification::fake();

    $user = User::factory()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($user);

    $this->getJson('/api/auth/verify-email/request')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('does not send a notification when email is already verified', function () {
    Notification::fake();

    $user = User::factory()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($user);

    $this->getJson('/api/auth/verify-email/request')->assertOk();

    Notification::assertNothingSent();
});

test('only sends notification to the authenticated user, not other users', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $bystander = User::factory()->unverified()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/auth/verify-email/request')->assertOk();

    Notification::assertSentTo($user, VerifyEmail::class);
    Notification::assertNotSentTo($bystander, VerifyEmail::class);
});

test('registration does not send a verification email automatically', function () {
    Notification::fake();

    $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ])->assertCreated();

    Notification::assertNothingSent();
});

test('user registered via API is unverified until they explicitly request verification', function () {
    Notification::fake();

    $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ])->assertCreated();

    $user = User::where('email', 'jane@example.com')->first();

    expect($user->email_verified_at)->toBeNull();
    Notification::assertNotSentTo($user, VerifyEmail::class);
});
