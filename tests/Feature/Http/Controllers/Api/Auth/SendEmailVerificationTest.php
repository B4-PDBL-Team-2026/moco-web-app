<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

test('unauthenticated request returns 401', function () {
    $this->postJson('/api/auth/verify-email/request')
        ->assertUnauthorized();
});

// Unverified user

test('returns 200 for an unverified user', function () {
    Notification::fake();

    Sanctum::actingAs(User::factory()->unverified()->create());

    $this->postJson('/api/auth/verify-email/request')->assertOk();
});

test('response contains success status for an unverified user', function () {
    Notification::fake();

    Sanctum::actingAs(User::factory()->unverified()->create());

    $this->postJson('/api/auth/verify-email/request')
        ->assertOk()
        ->assertJsonPath('data.status', 'success');
});

test('response contains the sent confirmation message for an unverified user', function () {
    Notification::fake();

    Sanctum::actingAs(User::factory()->unverified()->create());

    $this->postJson('/api/auth/verify-email/request')
        ->assertOk()
        ->assertJsonPath('data.message', 'Email verification link sent on email.');
});

test('sends a VerifyEmail notification to the authenticated unverified user', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/auth/verify-email/request')->assertOk();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('sends exactly one notification per request', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/auth/verify-email/request')->assertOk();

    Notification::assertSentToTimes($user, VerifyEmail::class, 1);
});

test('can resend by hitting the endpoint again', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/auth/verify-email/request')->assertOk();
    $this->postJson('/api/auth/verify-email/request')->assertOk();

    Notification::assertSentToTimes($user, VerifyEmail::class, 2);
});

// Already-verified user

test('returns 200 even when the user is already verified', function () {
    Notification::fake();

    Sanctum::actingAs(User::factory()->create(['email_verified_at' => now()]));

    $this->postJson('/api/auth/verify-email/request')->assertOk();
});

test('response contains success status when the user is already verified', function () {
    Notification::fake();

    Sanctum::actingAs(User::factory()->create(['email_verified_at' => now()]));

    $this->postJson('/api/auth/verify-email/request')
        ->assertOk()
        ->assertJsonPath('data.status', 'success');
});

test('response contains the already-verified message when the user is already verified', function () {
    Notification::fake();

    Sanctum::actingAs(User::factory()->create(['email_verified_at' => now()]));

    $this->postJson('/api/auth/verify-email/request')
        ->assertOk()
        ->assertJsonPath('data.message', 'Email is already verified.');
});

test('does not send a notification when the user is already verified', function () {
    Notification::fake();

    Sanctum::actingAs(User::factory()->create(['email_verified_at' => now()]));

    $this->postJson('/api/auth/verify-email/request')->assertOk();

    Notification::assertNothingSent();
});

// No cross-user leakage

test('only sends notification to the authenticated user, not other users', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $bystander = User::factory()->unverified()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/auth/verify-email/request')->assertOk();

    Notification::assertSentTo($user, VerifyEmail::class);
    Notification::assertNotSentTo($bystander, VerifyEmail::class);
});

// Registration no longer auto-sends

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

test('user registered via API has null email_verified_at', function () {
    Notification::fake();

    $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ])->assertCreated();

    expect(User::where('email', 'jane@example.com')->first()->email_verified_at)->toBeNull();
});
