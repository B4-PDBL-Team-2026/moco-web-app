<?php

namespace App\Domains\Transaction\Models;

use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
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

    protected static function newFactory(): Factory
    {
        return TransactionFactory::new();
    }

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
