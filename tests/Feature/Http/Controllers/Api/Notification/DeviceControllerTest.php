<?php

use App\Domains\User\Models\User;
use Illuminate\Support\Str;

// SECURITY & AUTHORIZATION TESTS

it('denies access to unauthenticated users', function () {
    $this->postJson('/api/notifications/device', [])->assertUnauthorized();
    $this->getJson('/api/notifications/device')->assertUnauthorized();
    $this->deleteJson('/api/notifications/device/random-id')->assertUnauthorized();
});

// POST: REGISTER DEVICE TESTS

it('successfully registers a new device', function () {
    $user = User::factory()->create();
    $payload = [
        'deviceId' => Str::random(16),
        'fcmToken' => 'fcm_token_valid_123',
        'deviceType' => 'android',
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/notifications/device', $payload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Device registered successfully.',
        ]);

    $this->assertDatabaseHas('user_devices', [
        'user_id' => $user->id,
        'device_id' => $payload['deviceId'],
        'fcm_token' => $payload['fcmToken'],
    ]);
});

it('fails to register device if validation fails', function () {
    $user = User::factory()->create();

    $payload = [];

    $response = $this->actingAs($user)
        ->postJson('/api/notifications/device', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['deviceId', 'fcmToken']);
});

// GET: RETRIEVE DEVICES TESTS

it('retrieves all registered devices for the authenticated user', function () {
    $user = User::factory()->create();
    $deviceId = Str::random(16);

    $user->devices()->create([
        'device_id' => $deviceId,
        'fcm_token' => 'fcm_token_123',
        'device_type' => 'android',
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/notifications/device');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'userId', 'deviceId', 'fcmToken', 'deviceType'],
            ],
        ])
        ->assertJsonPath('data.0.deviceId', $deviceId);
});

// DELETE: UNREGISTER DEVICE TESTS

it('successfully unregisters a device', function () {
    $user = User::factory()->create();
    $deviceId = Str::random(16);

    $user->devices()->create([
        'device_id' => $deviceId,
        'fcm_token' => 'fcm_token_123',
        'device_type' => 'android',
    ]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/notifications/device/{$deviceId}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('user_devices', [
        'user_id' => $user->id,
        'device_id' => $deviceId,
    ]);
});
