<?php

use App\Actions\Auth\VerifyEmailAction;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

it('menandai email sebagai verified jika user belum diverifikasi', function () {
    $userMock = Mockery::mock(User::class)->makePartial();

    $userMock->shouldReceive('hasVerifiedEmail')->once()->andReturn(false);
    $userMock->shouldReceive('markEmailAsVerified')->once()->andReturn(true);

    $action = new VerifyEmailAction;
    $result = $action->execute($userMock);

    expect($result['status'])->toBe('success')
        ->and($result['message'])->toBe('Email verified successfully.');
});

it('mengembalikan pesan success tapi tidak melakukan apa-apa jika email sudah verified sebelumnya', function () {
    $userMock = Mockery::mock(User::class)->makePartial();
    $userMock->shouldReceive('hasVerifiedEmail')->once()->andReturn(true);
    $userMock->shouldNotReceive('markEmailAsVerified');

    $action = new VerifyEmailAction;
    $result = $action->execute($userMock);

    expect($result['status'])->toBe('success')
        ->and($result['message'])->toBe('Email is already verified.');
});
