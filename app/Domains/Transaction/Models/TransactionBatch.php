<?php

namespace App\Domains\Transaction\Models;

use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;
use Database\Factories\TransactionBatchFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'note',
        'transaction_at',
    ];

    protected $casts = [
        'transaction_at' => 'immutable_datetime',
        'total_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transaction_batch_id');
    }

    protected static function newFactory(): Factory
    {
        return TransactionBatchFactory::new();
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->relationLoaded('transactions')) {
                    return null;
                }

                $total = $this->transactions->reduce(function (float $total, Transaction $transaction) {
                    $amount = (float) $transaction->amount;

                    return $transaction->type === TransactionType::INCOME
                        ? $total + $amount
                        : $total - $amount;
                }, 0.0);

                return $total <= 0 ? TransactionType::EXPENSE->value : TransactionType::INCOME->value;
            },
            set: fn ($value) => $value,
        );
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->relationLoaded('transactions')) {
                    return null;
                }

                return number_format(
                    abs(
                        $this->transactions->reduce(function (float $total, Transaction $transaction) {
                            $amount = (float) $transaction->amount;

                            return $transaction->type === TransactionType::INCOME
                                ? $total + $amount
                                : $total - $amount;
                        }, 0.0)
                    ), 2, '.', '');
            }
        );
    }
}
