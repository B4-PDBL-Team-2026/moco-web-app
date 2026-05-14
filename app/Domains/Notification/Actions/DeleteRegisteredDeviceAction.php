<?php

namespace App\Domains\Notification\Actions;

use App\Domains\User\Models\User;

class DeleteRegisteredDeviceAction
{
    public function execute(User $user, string $deviceId): bool
    {
        return $user->devices()
            ->where('device_id', '=', $deviceId)
            ->delete();
    }
}
