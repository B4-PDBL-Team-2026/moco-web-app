<?php

namespace Database\Factories;

use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionBatchFactory extends Factory
{
    protected $model = TransactionBatch::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'total_amount' => $this->faker->randomFloat(2, 1000, 1000000),
            'transaction_at' => $this->faker->date('now', 'utc'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function expense(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
        ]);
    }

    public function income(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
        ]);
    }
}
