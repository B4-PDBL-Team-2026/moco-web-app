<?php

namespace App\Models;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FixedCostOccurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixed_cost_template_id',
        'user_id',
        'cycle_key',
        'cycle_type',
        'cycle_start_date',
        'cycle_end_date',
        'due_date',
        'status',
        'amount',
        'name',
        'note',
        'category_id',
        'paid_at',
        'voided_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cycle_type' => CycleType::class,
        'status' => FixedCostOccurenceStatus::class,
        'due_date' => 'date',
        'cycle_start_date' => 'date',
        'cycle_end_date' => 'date',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(FixedCostTemplate::class, 'fixed_cost_template_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'fixed_cost_occurrence_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
