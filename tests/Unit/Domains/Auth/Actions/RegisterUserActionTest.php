<?php

use App\Domains\User\Actions\Auth\RegisterUserAction;
use App\Domains\User\DTOs\Auth\RegisterUserData;
use App\Domains\User\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->action = app(RegisterUserAction::class);

    $this->dto = new RegisterUserData(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'secret1234',
    );
});

it('returns a user, token, and requires_onboarding flag', function () {
    $result = $this->action->execute($this->dto);

    expect($result)->toHaveKeys(['user', 'token', 'requiresOnboarding']);
});

it('requires_onboarding is always true for a new registration', function () {
    $result = $this->action->execute($this->dto);

    expect($result['requiresOnboarding'])->toBeTrue();
});

it('returns a non-empty plain-text token', function () {
    $result = $this->action->execute($this->dto);

    expect($result['token'])->toBeString()->not->toBeEmpty();
});

it('returns the newly created User model', function () {
    $result = $this->action->execute($this->dto);

    expect($result['user'])->toBeInstanceOf(User::class)
        ->and($result['user']->exists)->toBeTrue();
});

it('persists the user to the database', function () {
    $this->action->execute($this->dto);

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

it('hashes the password before storing', function () {
    $this->action->execute($this->dto);

    $stored = User::where('email', 'john@example.com')->first();

    expect(Hash::check('secret1234', $stored->password))->toBeTrue()
        ->and($stored->password)->not->toBe('secret1234');
});

it('creates the user with email_verified_at as null (unverified)', function () {
    $result = $this->action->execute($this->dto);

    expect($result['user']->email_verified_at)->toBeNull();
});

it('does NOT dispatch the Registered event (email send is now explicit)', function () {
    Event::fake();

    $this->action->execute($this->dto);

    Event::assertNotDispatched(Registered::class);
});
