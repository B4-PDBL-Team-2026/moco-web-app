<?php

namespace App\Domains\Category\Models;

use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'type',
        'is_system',
        'user_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'type' => TransactionType::class,
    ];

    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
