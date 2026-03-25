<?php

namespace Database\Factories;

use App\Models\UserBudgetSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class UserBudgetSnapshotFactory extends Factory
{
    protected $model = UserBudgetSnapshot::class;

    public function definition(): array
    {
        return [
            'user_id' => $this->faker->randomNumber(),
            'current_balance' => $this->faker->randomFloat(),
            'reserved_cost' => $this->faker->randomFloat(),
            'daily_allowance' => $this->faker->randomFloat(),
            'actual_daily_allowance' => $this->faker->randomFloat(),
            'current_cycle_key' => $this->faker->word(),
            'cycle_start_date' => Carbon::now(),
            'cycle_end_date' => Carbon::now(),
            'remaining_days' => $this->faker->randomNumber(),
            'recalculated_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
