<?php

namespace Database\Factories;

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\CustomCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomCategoryFactory extends Factory
{
    protected $model = CustomCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'type' => $this->faker->randomElement([
                TransactionType::INCOME->value,
                TransactionType::EXPENSE->value,
            ]),
            'icon' => $this->faker->domainName(),
        ];
    }
}
