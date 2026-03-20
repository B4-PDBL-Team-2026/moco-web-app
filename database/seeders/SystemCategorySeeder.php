<?php

namespace Database\Seeders;

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\SystemCategory;
use Illuminate\Database\Seeder;

class SystemCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenses = config('constants.expenseCategories');
        foreach ($expenses as $name => $icon) {
            SystemCategory::query()->create([
                'name' => $name,
                'type' => TransactionType::EXPENSE->value,
                'icon' => $icon,
            ]);
        }

        $incomes = config('constants.incomeCategories');

        foreach ($incomes as $name => $icon) {
            SystemCategory::query()->create([
                'name' => $name,
                'type' => TransactionType::INCOME->value,
                'icon' => $icon,
            ]);
        }
    }
}
