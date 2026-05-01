<?php

use App\Domains\User\Actions\Auth\VerifyEmailAction;
use App\Domains\User\Models\User;

it('should mark new registered user email as unverified', function () {
    $userMock = Mockery::mock(User::class)->makePartial();

    $userMock->shouldReceive('hasVerifiedEmail')->once()->andReturn(false);
    $userMock->shouldReceive('markEmailAsVerified')->once()->andReturn(true);

    $action = new VerifyEmailAction;
    $result = $action->execute($userMock);

    expect($result['status'])->toBe('success')
        ->and($result['message'])->toBe('Email verified successfully.');
});

it('should return success and do nothing if email already verified', function () {
    $userMock = Mockery::mock(User::class)->makePartial();
    $userMock->shouldReceive('hasVerifiedEmail')->once()->andReturn(true);
    $userMock->shouldNotReceive('markEmailAsVerified');

    $action = new VerifyEmailAction;
    $result = $action->execute($userMock);

    expect($result['status'])->toBe('success')
        ->and($result['message'])->toBe('Email is already verified.');
});
