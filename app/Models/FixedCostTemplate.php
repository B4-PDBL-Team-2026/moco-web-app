<?php

namespace App\Models;

use App\Domains\Budgeting\Enums\CycleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedCostTemplate extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'amount',
        'cycle_type',
        'is_active',
        'due_day',
        'category_id',
        'category_type',
        'user_id',
    ];

    protected $casts = [
        'cycle_type' => CycleType::class,
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'due_day' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): MorphTo
    {
        return $this->morphTo();
    }
}
