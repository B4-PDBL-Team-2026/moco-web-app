<?php

namespace App\Domains\Budgeting\DTOs;

final readonly class UpdateDailyLimitData
{
    public function __construct(
        public string $flooringLimit,
        public string $ceilingLimit,
    ) {}
}
