<?php

namespace Database\Factories;

use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'name' => $this->faker->words(3, true),
            'amount' => $this->faker->randomFloat(2, 1000, 1000000),
            'type' => $this->faker->randomElement(['income', 'expense']),
            'note' => $this->faker->optional()->sentence(),
            'transaction_at' => $this->faker->date('now', 'utc'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function expense(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'category_id' => Category::factory()->expense()->create(),
        ]);
    }

    public function income(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
            'category_id' => Category::factory()->income()->create(),
        ]);
    }
}
