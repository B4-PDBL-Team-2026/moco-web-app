<?php

namespace App\Domains\Notification\Actions;

use App\Domains\Notification\DTOs\RegisterDeviceData;
use App\Domains\User\Models\User;

class RegisterUserDeviceAction
{
    public function execute(User $user, RegisterDeviceData $data): void
    {
        $user->devices()->updateOrCreate(
            ['device_id' => $data->deviceId],
            [
                'fcm_token' => $data->fcmToken,
                'device_type' => $data->deviceType,
            ]
        );
    }
}
