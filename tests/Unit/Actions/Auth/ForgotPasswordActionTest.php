<?php

use App\Actions\Auth\ForgotPasswordAction;
use Illuminate\Support\Facades\Password;

it('send reset password link correctly', function () {
    // Mocking facade
    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => 'test@moco.com'])
        ->andReturn(Password::RESET_LINK_SENT);

    $action = new ForgotPasswordAction;
    $result = $action->execute('test@moco.com');

    expect($result['status'])->toBe('success')
        ->and($result['message'])->toBe(__(Password::RESET_LINK_SENT));
});

it('fails to send reset password link if user not found', function () {
    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => 'invalid@moco.com'])
        ->andReturn(Password::INVALID_USER);

    $action = new ForgotPasswordAction;
    $result = $action->execute('invalid@moco.com');

    expect($result['status'])->toBe('error')
        ->and($result['message'])->toBe(__(Password::INVALID_USER));
});
