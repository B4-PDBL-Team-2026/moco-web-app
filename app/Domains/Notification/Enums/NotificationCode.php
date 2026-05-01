<?php

namespace App\Domains\Notification\Enums;

enum NotificationCode: string
{
    case FIXED_COST_REMINDER = 'FIXED_COST_REMINDER';

    case TRANSACTION_RECORD_REMINDER = 'TRANSACTION_RECORD_REMINDER';
}
