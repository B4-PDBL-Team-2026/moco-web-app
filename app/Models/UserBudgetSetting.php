<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBudgetSetting extends Model
{
    protected $fillable = [
        'user_id',
        'goal',
        'cycle_type',
        'cycle_start_date',
        'daily_ceiling_amount',
    ];

    protected $casts = [
        'cycle_start_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
