<?php

namespace App\Domains\Budgeting\DTOs;

class DailyAllowanceData
{
    public function __construct(
        public string $amount,
        public string $actualAmount,
    ) {}
}
