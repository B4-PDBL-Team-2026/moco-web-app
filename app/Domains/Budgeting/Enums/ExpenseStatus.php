<?php

namespace App\Domains\Budgeting\Enums;

enum ExpenseStatus: string
{
    case OVER = 'boros';
    case NORMAL = 'normal';
    case UNDER = 'hemat';
}
