<?php

namespace App\Models;

use App\Domains\Transactions\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'amount',
        'type',
        'note',
        'user_id',
        'category_id',
        'transaction_at',
        'source',
        'effective_at',
        'fixed_cost_occurrence_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => TransactionType::class,
        'transaction_at' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function fixedCostOccurrence(): BelongsTo
    {
        return $this->belongsTo(FixedCostOccurrence::class);
    }
}
