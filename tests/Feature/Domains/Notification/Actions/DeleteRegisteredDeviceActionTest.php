<?php

use App\Domains\Notification\Actions\DeleteRegisteredDeviceAction;
use App\Domains\User\Models\User;
use Illuminate\Support\Str;

it('successfully deletes a registered device for the user', function () {
    $user = User::factory()->create();
    $deviceId = Str::random(16);

    $user->devices()->create([
        'device_id' => $deviceId,
        'fcm_token' => 'dummy_fcm_token_123',
        'device_type' => 'android',
    ]);

    $action = app(DeleteRegisteredDeviceAction::class);

    $result = $action->execute($user, $deviceId);

    expect((bool) $result)->toBeTrue();
    $this->assertDatabaseMissing('user_devices', [
        'user_id' => $user->id,
        'device_id' => $deviceId,
    ]);
});

it('returns false when trying to delete a non-existent device', function () {
    $user = User::factory()->create();

    $action = app(DeleteRegisteredDeviceAction::class);
    $result = $action->execute($user, 'random_device_id_yang_nggak_ada');

    expect((bool) $result)->toBeFalse();
});

it('does not delete a device belonging to another user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $deviceId = Str::random(16);

    $user2->devices()->create([
        'device_id' => $deviceId,
        'fcm_token' => 'dummy_fcm_token_456',
        'device_type' => 'ios',
    ]);

    $action = app(DeleteRegisteredDeviceAction::class);

    $result = $action->execute($user1, $deviceId);

    expect((bool) $result)->toBeFalse();

    $this->assertDatabaseHas('user_devices', [
        'user_id' => $user2->id,
        'device_id' => $deviceId,
    ]);
});
