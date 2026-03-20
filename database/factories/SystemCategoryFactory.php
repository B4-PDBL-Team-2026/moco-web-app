<?php

namespace Database\Factories;

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\SystemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemCategoryFactory extends Factory
{
    protected $model = SystemCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => fake()->randomElement([TransactionType::INCOME->value, TransactionType::EXPENSE->value]),
        ];
    }
}
