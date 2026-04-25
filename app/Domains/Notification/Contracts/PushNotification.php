<?php

namespace App\Domains\Notification\Contracts;

use App\Domains\Notification\DTOs\PushMessage;

interface PushNotification
{
    /**
     * @return bool True if success, false if fails/invalid token
     */
    public function send(PushMessage $message): bool;
}
