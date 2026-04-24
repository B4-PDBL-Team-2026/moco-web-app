<?php

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Category;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;

/**
 * Setup user with budget settings and initial balance
 */
function setupUserWithBudget(array $settingOverrides = [], string $initialTransactionDate = '2026-03-01'): array
{
    $user = User::factory()->create();

    $category = Category::factory()->income()->create();

    $defaultSettings = [
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '1000.00',
        'flooring_limit' => '0.00',
        'ceiling_limit' => '999999.00',
        'timezone' => 'Asia/Jakarta',
    ];

    $settings = UserBudgetSetting::query()->create(array_merge($defaultSettings, $settingOverrides));

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'fixed_cost_occurrence_id' => null,
        'type' => TransactionType::INCOME->value,
        'source' => TransactionSource::INITIAL_BALANCE->value,
        'name' => 'initial balance',
        'amount' => $settings->initial_balance,
        'transaction_at' => $initialTransactionDate,
    ]);

    return [$user, $category];
}

function amountSetup(string $status = 'void', string $balance = '500000.00'): array
{
    $user = User::factory()->create();
    $category = Category::factory()->expense()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '0.00',
        'flooring_limit' => '0.00',
        'ceiling_limit' => '999999.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 10,
        'current_balance' => $balance,
    ]);

    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $occurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'due_date' => '2026-03-15',
        'status' => $status,
        'amount' => '150000.00',
        'name' => 'Gym',
        'category_id' => $category->id,
        'voided_at' => $status === 'void' ? now() : null,
    ]);

    return [$user, $occurrence, $category];
}
