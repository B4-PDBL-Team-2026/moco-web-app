<?php

namespace Database\Factories;

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\CustomCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomCategoryFactory extends Factory
{
    protected $model = CustomCategory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // relasi ke user
            'name' => $this->faker->name(),
            'type' => $this->faker->randomElement([
                TransactionType::INCOME->value,
                TransactionType::EXPENSE->value,
            ]),
            'icon' => $this->faker->domainName(),
        ];
    }
}
