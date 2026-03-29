<?php

use App\Domains\Budgeting\Actions\GetDashboardSummaryAction;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\FixedCostOccurrence;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;

it('returns correct dashboard summary', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'cycle_type' => 'monthly',
        'ceiling_limit' => '100000',
        'flooring_limit' => '30000',
        'timezone' => 'Asia/Jakarta',
    ]);

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '4500000',
        'daily_allowance_limit' => '100000',
        'remaining_daily_allowance' => '95000',
        'raw_daily_allowance' => '97500',
        'current_cycle_key' => now()->format('Y-m'),
    ]);

    $result = app(GetDashboardSummaryAction::class)
        ->execute($user, CarbonImmutable::now());

    expect($result['current_balance'])->toBe(4500000)
        ->and($result['budget_cycle'])->toBe('monthly')
        ->and($result['safety_ceiling'])->toBe(100000)
        ->and($result['safety_flooring'])->toBe(30000)
        ->and($result['today_limit'])->toBe(100000)
        ->and($result['tomorrow_limit_prediction'])->toBe(95000)
        ->and($result['raw_today_limit'])->toBe(97500)
        ->and($result['unpaid_fixed_costs'])->toBeArray();
});

it('today_spent only counts expense transactions today and exclude fixed cost payment transactions record', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'timezone' => 'Asia/Jakarta',
    ]);

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_cycle_key' => now()->format('Y-m'),
    ]);

    $today = CarbonImmutable::now()->setTimezone('Asia/Jakarta')->toDateString();
    $yesterday = CarbonImmutable::now()->subDay()->setTimezone('Asia/Jakarta')->toDateString();

    $category = SystemCategory::factory()->create(['type' => 'expense']);

    // today expense's must be included
    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '50000',
        'transaction_date' => $today,
    ]);

    // today income's must not be included
    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::INCOME->value,
        'amount' => '200000',
        'transaction_date' => $today,
    ]);

    // yesterday expense's must not be included
    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '30000',
        'transaction_date' => now()->subDay()->toDateString(),
    ]);

    // today fixed cost payment must not be included
    Transaction::factory()->createQuietly([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'source' => TransactionSource::FIXED_COST_PAYMENT->value,
        'amount' => '1000000',
        'transaction_date' => $today,
    ]);

    $result = app(GetDashboardSummaryAction::class)
        ->execute($user, CarbonImmutable::now());

    expect($result['today_spent'])->toBe(50000);
});

it('unpaid_fixed_costs only includes pending and overdue occurrences', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'timezone' => 'Asia/Jakarta',
    ]);

    $cycleKey = now()->format('Y-m');

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_cycle_key' => $cycleKey,
    ]);

    // PENDING → masuk
    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => $cycleKey,
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'amount' => '500000',
    ]);

    // OVERDUE → masuk
    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => $cycleKey,
        'status' => FixedCostOccurenceStatus::OVERDUE->value,
        'amount' => '300000',
    ]);

    // PAID → tidak masuk
    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => $cycleKey,
        'status' => FixedCostOccurenceStatus::PAID->value,
        'amount' => '200000',
    ]);

    $result = app(GetDashboardSummaryAction::class)
        ->execute($user, CarbonImmutable::now());

    expect($result['unpaid_fixed_costs'])->toHaveCount(2);
});
