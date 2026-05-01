<?php

namespace App\Domains\Notification\DTOs;

class RegisterDeviceData
{
    public function __construct(
        public string $deviceId,
        public ?string $deviceType,
        public string $fcmToken,
    ) {}
}
