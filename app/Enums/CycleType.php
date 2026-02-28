<?php

namespace App\Enums;

enum Goal: string
{
    case THRIFTY = 'si hemat';
    case NORMAL = 'normal';
    case HEDONIST = 'si paling hedon';
}

enum CycleType: string
{
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
}

enum DeductionType: string
{
    case IN = 'in';
    case OUT = 'out';
}

enum TransactionType: string
{
    case INCOME = 'income';
    case OUTCOME = 'outcome';
}

enum ExpenseStatus: string
{
    case OVER = 'boros';
    case NORMAL = 'normal';
    case UNDER = 'hemat';
}
