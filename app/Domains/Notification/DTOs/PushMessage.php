<?php

namespace App\Domains\Notification\DTOs;

class PushMessage
{
    public function __construct(
        public string $deviceToken,
        public string $title,
        public string $body,
        public array $data = []
    ) {}
}
