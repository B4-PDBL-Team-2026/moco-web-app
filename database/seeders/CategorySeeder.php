<?php

namespace Database\Seeders;

use App\Domains\Category\Actions\GetAllSystemCategoriesAction;
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
            Category::query()->updateOrCreate(
                [
                    'name' => $name,
                    'type' => TransactionType::EXPENSE->value,
                    'is_system' => true,
                ],
                [
                    'icon' => $icon,
                ]
            );
        }

        $incomes = config('constants.incomeCategories');

        foreach ($incomes as $name => $icon) {
            Category::query()->updateOrCreate(
                [
                    'name' => $name,
                    'type' => TransactionType::INCOME->value,
                    'is_system' => true,
                ],
                [
                    'icon' => $icon,
                ]
            );
        }

        GetAllSystemCategoriesAction::clearCache();
    }
}
