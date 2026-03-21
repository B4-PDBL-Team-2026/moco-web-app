<?php

namespace App\Models;

use App\Domains\Budgeting\Enums\CycleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBudgetSetting extends Model
{
    protected $fillable = [
        'user_id',
        'cycle_type',
        'ceiling_limit',
        'flooring_limit',
        'initial_balance',
        'timezone',
    ];

    protected $casts = [
        'cycle_type' => CycleType::class,
        'flooring_limit' => 'decimal:2',
        'ceiling_limit' => 'decimal:2',
        'initial_balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
