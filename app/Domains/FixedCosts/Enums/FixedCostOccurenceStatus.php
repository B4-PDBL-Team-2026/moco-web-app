<?php

namespace App\Domains\FixedCosts\Enums;

enum FixedCostOccurenceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case VOID = 'void';
    case OVERDUE = 'overdue';
}
