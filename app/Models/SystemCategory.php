<?php

namespace App\Models;

use App\Domains\Transactions\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SystemCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'icon',
        'colors',
        'type',
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
        return $this->morphMany(FixedCostTempl::class, 'fixed_cost_template');
    }
}
