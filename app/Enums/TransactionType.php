<?php

namespace App\Enums;

enum TransactionType: string
{
    case INCOME = 'income';
    case OUTCOME = 'outcome';
    case EXPENSE = 'expense';
}
