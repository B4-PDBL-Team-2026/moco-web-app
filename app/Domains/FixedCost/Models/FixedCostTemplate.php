<?php

namespace App\Domains\FixedCost\Models;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Category\Models\Category;
use App\Domains\User\Models\User;
use Database\Factories\FixedCostTemplateFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedCostTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'amount',
        'cycle_type',
        'is_active',
        'due_day',
        'category_id',
        'user_id',
    ];

    protected $casts = [
        'cycle_type' => CycleType::class,
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'due_day' => 'integer',
    ];

    protected static function newFactory(): Factory
    {
        return FixedCostTemplateFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
