<?php

namespace Database\Factories;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Random\RandomException;

class FixedCostOccurrenceFactory extends Factory
{
    protected $model = FixedCostOccurrence::class;

    /**
     * @throws RandomException
     */
    public function definition(): array
    {
        $now = now();
        $cycleType = Arr::random(CycleType::cases());

        return [
            'fixed_cost_template_id' => FixedCostTemplate::factory(),
            'user_id' => User::factory(),
            'name' => Arr::random(['Cicilan Motor', 'Uang Kos', 'Langganan Internet', 'Listrik', 'Netflix']).' '.rand(1, 99),
            'amount' => random_int(10000, 1000000),
            'cycle_type' => $cycleType->value,
            'cycle_key' => $cycleType === CycleType::MONTHLY
                ? $now->format('Y-m')
                : $now->format('o-\WW'),
            'due_date' => $now->toImmutable(),
            'status' => FixedCostOccurenceStatus::PENDING->value,
            'category_id' => Category::factory()->expense(),
            'paid_at' => null,
            'voided_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FixedCostOccurenceStatus::PAID->value,
            'paid_at' => now(),
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'cycle_type' => CycleType::WEEKLY->value,
            'cycle_key' => now()->format('o-\WW'),
        ]);
    }
}
