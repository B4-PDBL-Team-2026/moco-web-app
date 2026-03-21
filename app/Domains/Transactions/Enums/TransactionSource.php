<?php

namespace App\Domains\Transactions\Enums;

enum TransactionSource: string
{
    case INITIAL_BALANCE = 'initial_balance';
    case MANUAL = 'manual';
    case FIXED_COST_PAYMENT = 'fixed_cost_payment';
}
