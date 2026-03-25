<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBudgetSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_balance',
        'reserved_cost',
        'remaining_daily_allowance',
        'raw_daily_allowance',
        'current_cycle_key',
        'cycle_start_date',
        'cycle_end_date',
        'remaining_days',
        'recalculated_at',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'reserved_cost' => 'decimal:2',
        'remaining_daily_allowance' => 'decimal:2',
        'raw_daily_allowance' => 'decimal:2',
        'cycle_start_date' => 'date',
        'cycle_end_date' => 'date',
        'recalculated_at' => 'datetime',
    ];
}
