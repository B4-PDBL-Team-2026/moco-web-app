<?php

namespace App\Domains\Transaction\Enums;

enum TransactionFeedType: string
{
    case SINGLE = 'single';
    case BATCH = 'batch';
}
