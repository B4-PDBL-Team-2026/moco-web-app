<?php

namespace Database\Factories;

use App\Models\SystemCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => SystemCategory::factory(),
            'category_type' => (new SystemCategory)->getMorphClass(),
            'name' => $this->faker->words(3, true),
            'amount' => $this->faker->randomFloat(2, 1000, 1000000),
            'type' => $this->faker->randomElement(['income', 'expense']),
            'note' => $this->faker->optional()->sentence(),
            'transaction_at' => $this->faker->date('now', 'utc'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
