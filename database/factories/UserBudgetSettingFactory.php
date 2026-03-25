<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserBudgetSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class UserBudgetSettingFactory extends Factory
{
    protected $model = UserBudgetSetting::class;

    public function definition(): array
    {
        return [
            'cycle_type' => $this->faker->word(),
            'ceiling_limit' => $this->faker->randomFloat(),
            'flooring_limit' => $this->faker->randomFloat(),
            'initial_balance' => $this->faker->randomFloat(),
            'timezone' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}
