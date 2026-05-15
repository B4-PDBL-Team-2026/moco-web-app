<?php

use App\Domains\User\Actions\Auth\SendEmailVerificationAction;
use App\Domains\User\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->action = app(SendEmailVerificationAction::class);
});

// Return shape

it('returns an array with status and message keys', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $result = $this->action->execute($user);

    expect($result)->toBeArray()->toHaveKeys(['status', 'message']);
});

// Unverified user

it('returns success status for an unverified user', function () {
    Notification::fake();

    $result = $this->action->execute(User::factory()->unverified()->create());

    expect($result['status'])->toBe('success');
});

it('returns the sent confirmation message for an unverified user', function () {
    Notification::fake();

    $result = $this->action->execute(User::factory()->unverified()->create());

    expect($result['message'])->toBe(__('verification.sent'));
});

it('sends a VerifyEmail notification to an unverified user', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->action->execute($user);

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('sends exactly one notification per call', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->action->execute($user);

    Notification::assertSentToTimes($user, VerifyEmail::class, 1);
});

it('can be called multiple times to resend the notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->action->execute($user);
    $this->action->execute($user);

    Notification::assertSentToTimes($user, VerifyEmail::class, 2);
});

// Already-verified user

it('returns success status when the user is already verified', function () {
    $result = $this->action->execute(User::factory()->create(['email_verified_at' => now()]));

    expect($result['status'])->toBe('success');
});

it('returns the already-verified message when the user is already verified', function () {
    $result = $this->action->execute(User::factory()->create(['email_verified_at' => now()]));

    expect($result['message'])->toBe(__('verification.already_verified'));
});

it('does not send a notification when the user is already verified', function () {
    Notification::fake();

    $this->action->execute(User::factory()->create(['email_verified_at' => now()]));

    Notification::assertNothingSent();
});

it('does not throw when the user is already verified', function () {
    expect(fn () => $this->action->execute(User::factory()->create(['email_verified_at' => now()])))
        ->not->toThrow(Throwable::class);
});

// No side-effects on other users

it('does not send a notification to other users', function () {
    Notification::fake();

    $target = User::factory()->unverified()->create();
    $bystander = User::factory()->unverified()->create();

    $this->action->execute($target);

    Notification::assertNotSentTo($bystander, VerifyEmail::class);
});
