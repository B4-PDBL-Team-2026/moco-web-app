<?php

namespace Database\Seeders;

use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Enums\TransactionType;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenses = config('constants.expenseCategories');
        foreach ($expenses as $name => $icon) {
            Category::query()->create([
                'name' => $name,
                'type' => TransactionType::EXPENSE->value,
                'icon' => $icon,
                'is_system' => true,
            ]);
        }

        $incomes = config('constants.incomeCategories');

        foreach ($incomes as $name => $icon) {
            Category::query()->create([
                'name' => $name,
                'type' => TransactionType::INCOME->value,
                'icon' => $icon,
                'is_system' => true,
            ]);
        }
    }
}
