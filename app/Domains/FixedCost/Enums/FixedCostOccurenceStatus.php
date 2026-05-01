<?php

namespace App\Domains\FixedCost\Enums;

enum FixedCostOccurenceStatus: string
{
    case PAID = 'paid'; // when user confirmed the payment
    case PENDING = 'pending'; // when due date >= now
    case OVERDUE = 'overdue'; // when due date < now
    case SKIPPED = 'skipped'; // when user want to skip for current cycle
    case VOID = 'void'; // deleted template
}
