<?php

use App\Domains\User\Actions\Auth\ResetPasswordAction;
use App\Domains\User\DTOs\Auth\ResetPasswordData;
use Illuminate\Support\Facades\Password;

it('should return success array on success reset password action', function () {
    Password::shouldReceive('reset')
        ->once()
        ->andReturn(Password::PASSWORD_RESET);

    $action = new ResetPasswordAction;
    $result = $action->execute(new ResetPasswordData('test@moco.com', 'PasswordBaru123!', 'token-valid'));

    expect($result['status'])->toBe('success')
        ->and($result['message'])->toBe(__(Password::PASSWORD_RESET));
});

it('should return error on failed reset password action', function () {
    Password::shouldReceive('reset')
        ->once()
        ->andReturn(Password::INVALID_TOKEN);

    $action = new ResetPasswordAction;
    $result = $action->execute(new ResetPasswordData('test@moco.com', 'PasswordBaru123!', 'token-valid'));

    expect($result['status'])->toBe('error')
        ->and($result['message'])->toBe(__(Password::INVALID_TOKEN));
});
