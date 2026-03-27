<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SystemCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => fake()->randomElement(['income', 'expense']),
        ];
    }
}