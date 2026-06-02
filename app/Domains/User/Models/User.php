<?php

namespace App\Domains\User\Models;

use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\Transaction\Models\Transaction;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_url',
        'google_id',
        'email_verified_at',
        'has_onboarded',
        'status',
        'ban_duration',
        'banned_until',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_onboarded' => 'boolean',
            'email_verified_at' => 'datetime',
            'banned_until' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // relation methods
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function fixedCosts(): HasMany
    {
        return $this->hasMany(FixedCostTemplate::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgetSetting(): HasOne
    {
        return $this->hasOne(UserBudgetSetting::class, 'user_id', 'id');
    }

    public function isRequireOnboarding(): bool
    {
        return ! $this->has_onboarded;
    }

    /**
     * Returns true when the user is currently under an active ban.
     *
     * A ban is active when status === 'banned' AND either:
     *   - banned_until is null  (permanent / no expiry set yet), or
     *   - banned_until is in the future (time-limited ban not yet expired).
     */
    public function isBanned(): bool
    {
        if ($this->status !== 'banned') {
            return false;
        }

        return $this->banned_until === null || $this->banned_until->isFuture();
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class, 'user_id', 'id');
    }

    public function routeNotificationForFcm(): array
    {
        return $this->devices()->pluck('fcm_token')->toArray();
    }
}
