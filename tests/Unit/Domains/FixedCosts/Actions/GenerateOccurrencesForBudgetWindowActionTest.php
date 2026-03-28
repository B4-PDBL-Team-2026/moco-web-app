<?php

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\Actions\GenerateOccurencesForBudgetWindowAction;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

it('marks occurrence as pending if due date is exactly today', function () {
    $user = User::factory()->create([
        'created_at' => CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
    ]);

    $category = SystemCategory::factory()->create();

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Internet',
        'amount' => '300000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 15,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    app(GenerateOccurencesForBudgetWindowAction::class)->execute(
        userId: $user->id,
        budgetStartDate: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetEndDate: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
        now: CarbonImmutable::parse('2026-03-15', 'Asia/Jakarta'),
    );

    $occurrence = FixedCostOccurrence::query()->first();

    expect($occurrence)->not->toBeNull()
        ->and($occurrence->status->value)->toBe(FixedCostOccurenceStatus::PENDING->value);
});

it('generates one monthly occurrence inside a monthly budget window', function () {
    $user = User::factory()->create([
        'created_at' => CarbonImmutable::parse('2026-03-01')->startOfDay(),
    ]);
    $category = SystemCategory::factory()->create();

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Rent',
        'amount' => '1500000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 25,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    app(GenerateOccurencesForBudgetWindowAction::class)->execute(
        userId: $user->id,
        budgetStartDate: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetEndDate: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
        now: CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'),
    );

    $occurrence = FixedCostOccurrence::query()->first();

    expect($occurrence)->not->toBeNull()
        ->and($occurrence->cycle_key)->toBe('2026-03')
        ->and($occurrence->due_date->toDateString())->toBe('2026-03-25')
        ->and($occurrence->status->value)->toBe(FixedCostOccurenceStatus::PENDING->value);
});

it('generates all weekly occurrences inside a monthly budget window correctly', function () {
    $user = User::factory()->create([
        'created_at' => CarbonImmutable::parse('2026-03-12', 'Asia/Jakarta'),
    ]);

    $category = SystemCategory::factory()->create();

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Weekly saving',
        'amount' => '50000.00',
        'cycle_type' => CycleType::WEEKLY->value,
        'due_day' => 3,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    app(GenerateOccurencesForBudgetWindowAction::class)->execute(
        userId: $user->id,
        budgetStartDate: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetEndDate: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
        now: CarbonImmutable::parse('2026-03-12', 'Asia/Jakarta'),
    );

    $dueDates = FixedCostOccurrence::query()
        ->orderBy('due_date')
        ->pluck('due_date')
        ->map(fn ($date) => Carbon::parse($date)->toDateString())
        ->values()
        ->all();

    expect($dueDates)->toBe([
        '2026-03-18',
        '2026-03-25',
    ]);
});

it('generates occurrences for both monthly and weekly templates in a monthly budget window correctly', function () {
    $user = User::factory()->create([
        'created_at' => CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
    ]);

    $category = SystemCategory::factory()->create();

    // monthly fixed cost template
    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Monthly Rent',
        'amount' => '1000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 20,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    // weekly fixed cost template
    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Weekly Trash',
        'amount' => '50.00',
        'cycle_type' => CycleType::WEEKLY->value,
        'due_day' => 3,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    app(GenerateOccurencesForBudgetWindowAction::class)->execute(
        userId: $user->id,
        budgetStartDate: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetEndDate: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
        now: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
    );

    expect(FixedCostOccurrence::count())->toBe(5);

    $monthlyExists = FixedCostOccurrence::query()->where('name', 'Monthly Rent')->exists();
    $weeklyCount = FixedCostOccurrence::query()->where('name', 'Weekly Trash')->count();

    expect($monthlyExists)->toBeTrue()
        ->and($weeklyCount)->toBe(4);
});

it('clamps monthly due day to end of month', function () {
    $user = User::factory()->create([
        'created_at' => CarbonImmutable::parse('2026-02-01', 'Asia/Jakarta'),
    ]);
    $category = SystemCategory::factory()->create();

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Late month',
        'amount' => '10000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 31,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    app(GenerateOccurencesForBudgetWindowAction::class)->execute(
        userId: $user->id,
        budgetStartDate: CarbonImmutable::parse('2026-02-01', 'Asia/Jakarta'),
        budgetEndDate: CarbonImmutable::parse('2026-02-28', 'Asia/Jakarta'),
        now: CarbonImmutable::parse('2026-02-10', 'Asia/Jakarta'),
    );

    expect(FixedCostOccurrence::query()->first()->due_date->toDateString())->toBe('2026-02-28');
});

it('clamps invalid weekly due day to valid bounds (1 to 7)', function () {
    $user = User::factory()->create([
        'created_at' => CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
    ]);

    $category = SystemCategory::factory()->create();

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Invalid Week Day',
        'amount' => '10000.00',
        'cycle_type' => CycleType::WEEKLY->value,
        'due_day' => 10,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    app(GenerateOccurencesForBudgetWindowAction::class)->execute(
        userId: $user->id,
        budgetStartDate: CarbonImmutable::parse('2026-03-02', 'Asia/Jakarta'),
        budgetEndDate: CarbonImmutable::parse('2026-03-08', 'Asia/Jakarta'),
        now: CarbonImmutable::parse('2026-03-02', 'Asia/Jakarta'),
    );

    $occurrence = FixedCostOccurrence::query()->first();

    expect($occurrence)->not->toBeNull()
        ->and($occurrence->due_date->toDateString())->toBe('2026-03-08');
});

it('marks occurrence overdue when due date has passed in a weekly budget window', function () {
    $user = User::factory()->create([
        'created_at' => CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
    ]);

    $category = SystemCategory::factory()->create();

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Water',
        'amount' => '50000.00',
        'cycle_type' => CycleType::WEEKLY->value,
        'due_day' => 5,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    app(GenerateOccurencesForBudgetWindowAction::class)->execute(
        userId: $user->id,
        budgetStartDate: CarbonImmutable::parse('2026-03-02', 'Asia/Jakarta'),
        budgetEndDate: CarbonImmutable::parse('2026-03-08', 'Asia/Jakarta'),
        now: CarbonImmutable::parse('2026-03-07', 'Asia/Jakarta'),
    );

    $occurrence = FixedCostOccurrence::query()->first();

    expect($occurrence)->not->toBeNull();
});

it('does not create duplicate occurrence for same template and cycle', function () {
    $user = User::factory()->create([
        'created_at' => CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
    ]);

    $category = SystemCategory::factory()->create();

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Rent',
        'amount' => '1000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 25,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    $action = app(GenerateOccurencesForBudgetWindowAction::class);

    $action->execute(
        userId: $user->id,
        budgetStartDate: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetEndDate: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
        now: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
    );

    $action->execute(
        userId: $user->id,
        budgetStartDate: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetEndDate: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
        now: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
    );

    expect(FixedCostOccurrence::count())->toBe(1);
});

it('ignores inactive templates when generating occurrences for window', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Paused cost',
        'amount' => '1000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 25,
        'is_active' => false,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    app(GenerateOccurencesForBudgetWindowAction::class)->execute(
        userId: $user->id,
        budgetStartDate: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetEndDate: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
        now: CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'),
    );

    expect(FixedCostOccurrence::count())->toBe(0);
});
