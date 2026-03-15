<?php

namespace App\Enums;

enum CycleType: string
{
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public function countDays(): int
    {
        return match ($this) {
            self::WEEKLY => 7,
            self::MONTHLY => 30,
        };
    }
}
