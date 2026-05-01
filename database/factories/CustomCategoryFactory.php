<?php

namespace Database\Factories;

use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;
use App\Models\CustomCategory;
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
