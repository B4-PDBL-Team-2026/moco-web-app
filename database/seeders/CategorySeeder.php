<?php

namespace Database\Seeders;

use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenses = config('constants.expenseCategories');
        foreach ($expenses as $expense) {
            Category::query()->create([
                'name' => $expense,
                'type' => TransactionType::EXPENSE->value,
            ]);
        }

        $incomes = config('constants.incomeCategories');

        foreach ($incomes as $income) {
            Category::query()->create([
                'name' => $income,
                'type' => TransactionType::INCOME->value,
            ]);
        }
    }
}
