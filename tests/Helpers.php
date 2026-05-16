<?php

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;

/**
 * Setup user with budget settings and initial balance
 */
function setupUserWithBudget(array $settingOverrides = [], string $initialTransactionDate = '2026-03-01'): array
{
    $user = User::factory()->create();
    $category = Category::factory()->income()->create();

    // Support both 'initial_balance' and 'balance' keys
    $initialBalance = $settingOverrides['initial_balance'] ?? $settingOverrides['balance'] ?? '1000.00';

    $settings = UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => $settingOverrides['cycle_type'] ?? CycleType::MONTHLY->value,
        'initial_balance' => $initialBalance,
        'flooring_limit' => $settingOverrides['flooring_limit'] ?? '0.00',
        'ceiling_limit' => $settingOverrides['ceiling_limit'] ?? '999999.00',
        'timezone' => $settingOverrides['timezone'] ?? 'Asia/Jakarta',
    ]);

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

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => $settingOverrides['current_balance'] ?? $initialBalance,
        'reserved_cost' => $settingOverrides['reserved_cost'] ?? '0.00',
        'remaining_daily_allowance' => $settingOverrides['remaining_daily_allowance'] ?? '100.00',
        'daily_allowance_limit' => $settingOverrides['daily_allowance_limit'] ?? '100.00',
        'raw_daily_allowance' => $settingOverrides['raw_daily_allowance'] ?? '100.00',
        'current_cycle_key' => $settingOverrides['current_cycle_key'] ?? '2026-03',
        'cycle_start_date' => $settingOverrides['cycle_start_date'] ?? '2026-03-01',
        'cycle_end_date' => $settingOverrides['cycle_end_date'] ?? '2026-03-31',
        'remaining_days' => $settingOverrides['remaining_days'] ?? 12,

        // Force default recalculated_at to yesterday so action limit recalculation triggers properly
        'recalculated_at' => $settingOverrides['recalculated_at'] ?? now()->subDay()->utc(),
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

function catchException(callable $fn, string $exceptionClass): object
{
    try {
        $fn();
        throw new Exception("Expected {$exceptionClass} but nothing was thrown.");
    } catch (Throwable $e) {
        expect($e)->toBeInstanceOf($exceptionClass);

        return $e;
    }
}

function indexTemplateSetup(): array
{
    $user = User::factory()->create(['has_onboarded' => true]);
    $cat = Category::factory()->expense()->create();

    return [$user, $cat];
}

function createTemplate(User $user, Category $cat, array $overrides = []): FixedCostTemplate
{
    return FixedCostTemplate::factory()->create(array_merge([
        'user_id' => $user->id,
        'name' => 'Netflix',
        'amount' => '150000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 15,
        'is_active' => true,
        'category_id' => $cat->id,
    ], $overrides));
}
