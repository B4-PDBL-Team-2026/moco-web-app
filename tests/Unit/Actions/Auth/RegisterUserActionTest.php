<?php

use App\Actions\Auth\RegisterUserAction;
use App\DTOs\Auth\RegisterUserDTO;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it registers a user successfully', function () {
    // Arrange
    Event::fake();
    $action = new RegisterUserAction;
    $dto = new RegisterUserDTO(
        name: 'Test User',
        email: 'test@example.com',
        password: 'password'
    );

    // Act
    $result = $action->execute($dto);

    // Assert
    expect($result)->toBeArray()
        ->toHaveKey('user')
        ->toHaveKey('token')
        ->toHaveKey('requires_onboarding', true)
        ->and($result['user'])->toBeInstanceOf(User::class)
        ->and($result['user']->name)->toBe('Test User')
        ->and($result['user']->email)->toBe('test@example.com')
        ->and(Hash::check('password', $result['user']->password))->toBeTrue();

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
        'goal' => null,
        'cycle_start' => null,
        'cycle_type' => null,
        'balance' => null,
    ]);

    Event::assertDispatched(Registered::class, function ($event) use ($result) {
        return $event->user->id === $result['user']->id;
    });
});
