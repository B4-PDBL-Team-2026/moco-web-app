<?php

namespace App\Models;

use App\Enums\DeductionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedCost extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'deduction_type',
        'amount',
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
            'amount' => 'decimal:2',
        ];
    }
}
