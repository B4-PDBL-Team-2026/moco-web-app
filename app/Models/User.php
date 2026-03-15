<?php

namespace App\Models;

use App\Enums\CycleType;
use App\Enums\Goal;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password', 'goal', 'cycle_type', 'cycle_start', 'balance', 'profile_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'goal' => Goal::class,
            'cycle_type' => CycleType::class,
            'cycle_start' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function fixedCosts(): HasMany
    {
        return $this->hasMany(FixedCost::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isRequireOnboarding(): bool
    {
        return is_null($this->goal) || is_null($this->cycle_type) || is_null($this->cycle_start) || is_null($this->balance);
    }
}
