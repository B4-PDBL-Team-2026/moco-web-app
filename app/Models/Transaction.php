<?php

namespace App\Models;

use App\Domains\Transactions\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'amount',
        'type',
        'note',
        'user_id',
        'category_id',
        'transaction_date',
        'source_type',
        'effective_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => TransactionType::class,
        'transaction_date' => 'date',
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
