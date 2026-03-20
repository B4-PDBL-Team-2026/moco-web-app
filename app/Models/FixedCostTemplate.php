<?php

namespace App\Models;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Budgeting\Enums\DeductionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedCostTemplate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'deduction_type',
        'amount',
        'cycle',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'deduction_type' => DeductionType::class,
            'cycle' => CycleType::class,
            'amount' => 'decimal:2',
        ];
    }
}
