<?php

namespace Database\Factories;

use App\Domains\Budgeting\Enums\CycleType;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FixedCostTemplateFactory extends Factory
{
    protected $model = FixedCostTemplate::class;

    public function definition(): array
    {
        $cycleType = $this->faker->randomElement(CycleType::cases());

        return [
            'name' => $this->faker->sentence(3),
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 50000, 5000000),
            'cycle_type' => $cycleType->value,
            'due_day' => $cycleType === CycleType::MONTHLY
                ? $this->faker->numberBetween(1, 28)
                : $this->faker->numberBetween(1, 7),
            'is_active' => true,
            'category_type' => SystemCategory::class,
            'category_id' => SystemCategory::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function monthly(?int $day = null): static
    {
        return $this->state(fn (array $attributes) => [
            'cycle_type' => CycleType::MONTHLY->value,
            'due_day' => $day ?? $this->faker->numberBetween(1, 28),
        ]);
    }

    public function weekly(?int $day = null): static
    {
        return $this->state(fn (array $attributes) => [
            'cycle_type' => CycleType::WEEKLY->value,
            'due_day' => $day ?? $this->faker->numberBetween(1, 7),
        ]);
    }
}
