<?php

namespace App\Domains\Budgeting\Models;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\User\Models\User;
use Database\Factories\UserBudgetSettingFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBudgetSetting extends Model
{
    use HasFactory;

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

    protected static function newFactory(): Factory
    {
        return UserBudgetSettingFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
