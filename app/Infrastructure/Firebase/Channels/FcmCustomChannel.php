<?php

namespace App\Infrastructure\Firebase\Channels;

use App\Domains\Notification\Contracts\PushNotification;
use App\Domains\Notification\DTOs\PushMessage;
use App\Domains\User\Models\User;
use Illuminate\Notifications\Notification;

readonly class FcmCustomChannel
{
    public function __construct(
        private PushNotification $pushNotification,
    ) {}

    public function send(User $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $messageData = $notification->toFcm();

        $tokens = $notifiable->routeNotificationFor('fcm', $notification);

        if (empty($tokens)) {
            return;
        }

        foreach ($tokens as $token) {
            $finalMessage = new PushMessage(
                deviceToken: $token,
                title: $messageData->title,
                body: $messageData->body,
                data: $messageData->data,
            );

            $this->pushNotification->send($finalMessage);
        }
    }
}
