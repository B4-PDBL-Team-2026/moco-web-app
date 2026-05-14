<?php

namespace App\Domains\Notification\Actions;

use App\Domains\User\Models\UserDevice;
use Illuminate\Support\Collection;

class GetAllRegisteredDeviceAction
{
    public function execute(int $userId): Collection
    {
        return UserDevice::query()
            ->where('user_id', $userId)
            ->get();
    }
}
