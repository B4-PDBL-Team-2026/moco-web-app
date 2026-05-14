<?php

use App\Domains\Notification\Actions\GetAllRegisteredDeviceAction;
use App\Domains\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

it('retrieves all registered devices belonging to the specific user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->devices()->create([
        'device_id' => Str::random(16),
        'fcm_token' => 'token_user_1_device_A',
        'device_type' => 'android',
    ]);
    $user1->devices()->create([
        'device_id' => Str::random(16),
        'fcm_token' => 'token_user_1_device_B',
        'device_type' => 'ios',
    ]);

    $user2->devices()->create([
        'device_id' => Str::random(16),
        'fcm_token' => 'token_user_2_device_A',
        'device_type' => 'android',
    ]);

    $action = app(GetAllRegisteredDeviceAction::class);
    $result = $action->execute($user1->id);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->pluck('fcm_token')->toArray())->toContain('token_user_1_device_A', 'token_user_1_device_B')
        ->and($result->pluck('fcm_token')->toArray())->not->toContain('token_user_2_device_A');
});

it('returns an empty collection if the user has no registered devices', function () {
    $user = User::factory()->create();

    $action = app(GetAllRegisteredDeviceAction::class);
    $result = $action->execute($user->id);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toBeEmpty();
});
