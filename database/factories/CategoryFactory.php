<?php

namespace Database\Factories;

use App\Domains\Category\Models\Category;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => fake()->randomElement(['income', 'expense']),
            'is_system' => true,
            'user_id' => null,
            'icon' => fake()->word(),
        ];
    }

    public function custom(?User $user = null): self
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'is_system' => false,
                'user_id' => $user ? $user->id : User::factory(),
            ];
        });
    }

    public function expense(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
        ]);
    }

    public function income(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
        ]);
    }
}
