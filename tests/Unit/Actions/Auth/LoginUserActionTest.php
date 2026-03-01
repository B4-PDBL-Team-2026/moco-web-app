<?php

use App\Actions\Auth\LoginUserAction;
use App\DTOs\Auth\LoginUserDTO;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it logs in a user successfully with correct credentials', function () {
    // Arrange
    $user = User::factory()->create([
        'email' => 'testlogin@example.com',
        'password' => Hash::make('CorrectPassword123!'),
    ]);

    $action = new LoginUserAction;
    $dto = new LoginUserDTO(
        email: 'testlogin@example.com',
        password: 'CorrectPassword123!'
    );

    // Act
    $result = $action->execute($dto);

    // Assert
    expect($result)->toBeArray()
        ->toHaveKey('user')
        ->toHaveKey('token')
        ->and($result['user']->id)->toBe($user->getAttribute('id'))
        ->and($result['requires_onboarding'])->toBeTrue()
        ->and($result['token'])->toBeString();
});

test('it throws validation exception when password is wrong', function () {
    // Arrange
    User::factory()->create([
        'email' => 'testlogin@example.com',
        'password' => Hash::make('CorrectPassword123!'),
    ]);

    $action = new LoginUserAction;
    $dto = new LoginUserDTO(
        email: 'testlogin@example.com',
        password: 'WrongPassword!!!'
    );

    // Act & Assert
    expect(fn () => $action->execute($dto))
        ->toThrow(ValidationException::class);
});

test('it throws validation exception when email is not found', function () {
    // Arrange
    $action = new LoginUserAction;
    $dto = new LoginUserDTO(
        email: 'notfound@example.com',
        password: 'SomePassword123!'
    );

    // Act & Assert
    expect(fn () => $action->execute($dto))
        ->toThrow(ValidationException::class);
});
