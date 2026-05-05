<?php

namespace App\Domains\Notification\Actions;

use App\Domains\User\Models\User;

class DeleteNotificationByIdAction
{
    public function execute(User $user, string $notificationId): bool
    {
        return $user->notifications()->findOrFail($notificationId)->delete();
    }
}
