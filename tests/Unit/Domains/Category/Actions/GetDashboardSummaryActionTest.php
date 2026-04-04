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

    expect($result['currentBalance'])->toBe(4500000)
        ->and($result['budgetCycle'])->toBe('monthly')
        ->and($result['safetyCeiling'])->toBe(100000)
        ->and($result['safetyFlooring'])->toBe(30000)
        ->and($result['todayLimit'])->toBe(100000)
        ->and($result['tomorrowLimitPrediction'])->toBe(95000)
        ->and($result['rawTodayLimit'])->toBe(97500)
        ->and($result['unpaidFixedCosts'])->toBeArray();
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
        'transaction_at' => $today,
    ]);

    // today income's must not be included
    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::INCOME->value,
        'amount' => '200000',
        'transaction_at' => $today,
    ]);

    // yesterday expense's must not be included
    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '30000',
        'transaction_at' => $yesterday,
    ]);

    // today fixed cost payment must not be included
    Transaction::factory()->createQuietly([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'source' => TransactionSource::FIXED_COST_PAYMENT->value,
        'amount' => '1000000',
        'transaction_at' => $today,
    ]);

    $result = app(GetDashboardSummaryAction::class)
        ->execute($user, CarbonImmutable::now());

    expect($result['todaySpent'])->toBe(50000);
});

it('calculates today_spent accurately across timezone boundaries', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'timezone' => 'Asia/Jakarta',
    ]);

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_cycle_key' => '2026-03',
    ]);

    $category = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE->value]);

    $nowUtc = CarbonImmutable::parse('2026-03-30 02:00:00', 'UTC');

    // Asia/Jakarta: 29 Maret 23:00 WIB)
    // UTC: 29 Maret 16:00:00 (must not be included)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '10000',
        'transaction_at' => '2026-03-29 16:00:00',
    ]);

    // Asia/Jakarta: 30 Maret 01:00 WIB
    // UTC: 29 Maret 18:00:00 (must be included)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '50000',
        'transaction_at' => '2026-03-29 18:00:00',
    ]);

    // Asia/Jakarta: 30 Maret 17:00 WIB
    // UTC: 30 Maret 10:00:00 (must be included)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '20000',
        'transaction_at' => '2026-03-30 10:00:00',
    ]);

    // Asia/Jakarta: 31 Maret 01:00 WIB
    // UTC: 30 Maret 18:00:00 (must not be included)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '100000',
        'transaction_at' => '2026-03-30 18:00:00',
    ]);

    // execute in server time
    $result = app(GetDashboardSummaryAction::class)
        ->execute($user, $nowUtc);

    // only two of three transactions must be calculated (50000 + 20000 = 70000)
    expect($result['todaySpent'])->toBe(70000);
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

    expect($result['unpaidFixedCosts'])->toHaveCount(2);
});
