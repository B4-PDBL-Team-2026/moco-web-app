<?php

namespace App\Domains\Transaction\Enums;

enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';
}
