<?php

namespace App\Domains\Budgeting\Enums;

enum Goal: string
{
    case THRIFTY = 'si hemat';
    case NORMAL = 'normal';
    case HEDONIST = 'si paling hedon';
}
