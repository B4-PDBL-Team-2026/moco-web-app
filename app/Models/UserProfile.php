<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_name',
        'avatar_url',
        'currency',
        'locale',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}