<?php

namespace App\Domains\FixedCost\Models;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
use Database\Factories\FixedCostOccurrenceFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
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

    protected static function newFactory(): Factory
    {
        return FixedCostOccurrenceFactory::new();
    }

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
