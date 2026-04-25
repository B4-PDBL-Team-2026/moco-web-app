<?php

namespace App\Domains\Notification\Actions;

use App\Domains\Notification\Contracts\PushNotification;
use App\Domains\Notification\DTOs\PushMessage;

final readonly class SendNotificationAction
{
    public function __construct(
        private PushNotification $pushNotification,
    ) {}

    public function execute(string $deviceToken, string $title, string $body, array $data = []): void
    {
        $message = new PushMessage($deviceToken, $title, $body, $data);

        $this->pushNotification->send($message);
    }

}
