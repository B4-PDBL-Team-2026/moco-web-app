<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'display_name' => $this->faker->name(),
            'avatar_url'   => $this->faker->optional()->imageUrl(),
            'currency'     => 'IDR',
            'locale'       => 'id_ID',
        ];
    }
}