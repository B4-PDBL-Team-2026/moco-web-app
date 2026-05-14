<?php

use App\Domains\Notification\Actions\RegisterUserDeviceAction;
use App\Domains\Notification\DTOs\RegisterDeviceData;
use App\Domains\User\Models\User;
use Illuminate\Support\Str;

it('creates a new registered device if it does not exist', function () {
    $user = User::factory()->create();
    $deviceId = Str::random();

    $data = new RegisterDeviceData(
        deviceId: $deviceId,
        deviceType: 'android',
        fcmToken: 'fcm_token_baru_123'
    );

    $action = app(RegisterUserDeviceAction::class);
    $action->execute($user, $data);

    $this->assertDatabaseHas('user_devices', [
        'user_id' => $user->id,
        'device_id' => $deviceId,
        'fcm_token' => 'fcm_token_baru_123',
        'device_type' => 'android',
    ]);

    expect($user->devices()->count())->toBe(1);
});

it('updates the fcm_token and device_type if the device_id already exists', function () {
    $user = User::factory()->create();
    $deviceId = Str::random();

    $user->devices()->create([
        'device_id' => $deviceId,
        'fcm_token' => 'token_lama_yang_udah_expired',
        'device_type' => 'android',
    ]);

    $data = new RegisterDeviceData(
        deviceId: $deviceId,
        deviceType: 'android',
        fcmToken: 'fcm_token_super_baru_456'
    );

    $action = app(RegisterUserDeviceAction::class);
    $action->execute($user, $data);

    $this->assertDatabaseHas('user_devices', [
        'user_id' => $user->id,
        'device_id' => $deviceId,
        'fcm_token' => 'fcm_token_super_baru_456',
    ]);

    expect($user->devices()->count())->toBe(1);
});
