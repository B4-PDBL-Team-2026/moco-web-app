<?php

namespace App\Models;

use App\Domains\Transactions\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CustomCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'colors',
        'type',
        'user_id',
    ];

    protected $casts = [
        'type' => TransactionType::class,
    ];

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'category');
    }

    public function fixedCostTemplates(): MorphMany
    {
        return $this->morphMany(FixedCostTemplate::class, 'fixed_cost_template');
    }
}
