<?php

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\Actions\GenerateCurrentCycleOccurrencesAction;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;
use Carbon\CarbonImmutable;

it('generates monthly occurrence correctly', function () {
    $user = User::factory()->create();
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

    app(GenerateCurrentCycleOccurrencesAction::class)->execute(
        userId: $user->id,
        now: CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'),
        timezone: 'Asia/Jakarta',
    );

    $occurrence = FixedCostOccurrence::query()->first();

    expect($occurrence)->not->toBeNull()
        ->and($occurrence->cycle_key)->toBe('2026-03')
        ->and($occurrence->due_date->toDateString())->toBe('2026-03-25')
        ->and($occurrence->status->value)->toBe(FixedCostOccurenceStatus::PENDING->value);
});

it('clamps monthly due day to end of month', function () {
    $user = User::factory()->create();
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

    app(GenerateCurrentCycleOccurrencesAction::class)->execute(
        userId: $user->id,
        now: CarbonImmutable::parse('2026-02-10', 'Asia/Jakarta'),
        timezone: 'Asia/Jakarta',
    );

    expect(FixedCostOccurrence::query()->first()->due_date->toDateString())->toBe('2026-02-28');
});

it('generates weekly occurrence correctly', function () {
    $user = User::factory()->create();
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

    app(GenerateCurrentCycleOccurrencesAction::class)->execute(
        userId: $user->id,
        now: CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'),
        timezone: 'Asia/Jakarta',
    );

    expect(FixedCostOccurrence::query()->first()->due_date->toDateString())->toBe('2026-03-18');
});

it('marks occurrence overdue when due date has passed', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    FixedCostTemplate::query()->create([
        'user_id' => $user->id,
        'name' => 'Water',
        'amount' => '50000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 5,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
    ]);

    app(GenerateCurrentCycleOccurrencesAction::class)->execute(
        userId: $user->id,
        now: CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'),
        timezone: 'Asia/Jakarta',
    );

    expect(FixedCostOccurrence::query()->first()->status->value)->toBe(FixedCostOccurenceStatus::OVERDUE->value);
});

it('does not create duplicate occurrence for same template and cycle', function () {
    $user = User::factory()->create();
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

    $action = app(GenerateCurrentCycleOccurrencesAction::class);

    $action->execute($user->id, CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'), 'Asia/Jakarta');
    $action->execute($user->id, CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'), 'Asia/Jakarta');

    expect(FixedCostOccurrence::count())->toBe(1);
});

it('ignores inactive templates', function () {
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

    app(GenerateCurrentCycleOccurrencesAction::class)->execute(
        userId: $user->id,
        now: CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'),
        timezone: 'Asia/Jakarta',
    );

    expect(FixedCostOccurrence::count())->toBe(0);
});
