<?php

namespace App\Domains\Notification\Actions;

use App\Domains\User\Models\UserDevice;

class GetAllRegisteredDeviceAction
{
    public function execute(int $userId)
    {
        return UserDevice::query()
            ->where('user_id', $userId)
            ->get();
    }
}
