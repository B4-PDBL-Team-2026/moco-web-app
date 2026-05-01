<?php

namespace App\Domains\Notification;

enum NotificationCode: string
{
    case FIXED_COST_REMINDER = 'FIXED_COST_REMINDER';

    case TRANSACTION_RECORD_REMINDER = 'TRANSACTION_RECORD_REMINDER';
}
