<?php

namespace Database\Factories;

use App\Domains\Budgeting\Enums\CycleType;
use App\Models\User;
use App\Models\UserBudgetSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class UserBudgetSettingFactory extends Factory
{
    protected $model = UserBudgetSetting::class;

    public function definition(): array
    {
        $cycleType = $this->faker->randomElement(CycleType::cases());

        return [
            'cycle_type' => $cycleType->value,
            'ceiling_limit' => $this->faker->randomFloat(),
            'flooring_limit' => $this->faker->randomFloat(),
            'initial_balance' => $this->faker->randomFloat(),
            'timezone' => $this->faker->randomElement(timezone_identifiers_list()),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'user_id' => User::factory(),
        ];
    }
}
