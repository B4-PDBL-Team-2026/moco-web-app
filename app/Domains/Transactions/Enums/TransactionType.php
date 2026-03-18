<?php

namespace App\Domains\Transactions\Enums;

enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';
}
