<?php

use App\Domains\Budgeting\Services\ReservedCostCalculator;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\User\Models\User;
use Carbon\CarbonImmutable;

it('returns 0.00 when user has no occurrences in the window', function () {
    $user = User::factory()->create();

    $result = app(ReservedCostCalculator::class)->calculateForBudgetWindow(
        userId: $user->id,
        budgetWindowStart: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetWindowEnd: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
    );

    expect($result)->toBe('0.00');
});

it('includes occurrences that fall exactly on the boundary dates (start and end)', function () {
    $user = User::factory()->create();

    // due at start date
    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-01',
        'amount' => '100.00',
    ]);

    // due at end date
    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-31',
        'amount' => '200.00',
    ]);

    $result = app(ReservedCostCalculator::class)->calculateForBudgetWindow(
        userId: $user->id,
        budgetWindowStart: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetWindowEnd: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
    );

    expect($result)->toBe('300.00');
});

it('does not sum occurrences belonging to other users', function () {
    $targetUser = User::factory()->create();
    $otherUser = User::factory()->create();

    FixedCostOccurrence::factory()->create([
        'user_id' => $targetUser->id,
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-15',
        'amount' => '100.00',
    ]);

    FixedCostOccurrence::factory()->create([
        'user_id' => $otherUser->id,
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-15',
        'amount' => '9999.00',
    ]);

    $result = app(ReservedCostCalculator::class)->calculateForBudgetWindow(
        userId: $targetUser->id,
        budgetWindowStart: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetWindowEnd: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
    );

    expect($result)->toBe('100.00');
});

it('calculates decimal amounts accurately without floating point issues', function () {
    $user = User::factory()->create();

    foreach (['2026-03-10', '2026-03-11', '2026-03-12'] as $dueDate) {
        FixedCostOccurrence::factory()->create([
            'user_id' => $user->id,
            'status' => FixedCostOccurenceStatus::PENDING->value,
            'due_date' => $dueDate,
            'amount' => '33.33',
        ]);
    }

    $result = app(ReservedCostCalculator::class)->calculateForBudgetWindow(
        userId: $user->id,
        budgetWindowStart: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetWindowEnd: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
    );

    expect($result)->toBe('99.99');
});

it('sums only reservable occurrences in current cycle', function () {
    $user = User::factory()->create();

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => '2026-03',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-25',
        'amount' => '100.00',
    ]);

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => '2026-03',
        'status' => FixedCostOccurenceStatus::OVERDUE->value,
        'due_date' => '2026-03-20',
        'amount' => '50.00',
    ]);

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => '2026-03',
        'status' => FixedCostOccurenceStatus::PAID->value,
        'due_date' => '2026-03-26',
        'amount' => '999.00',
    ]);

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => '2026-04',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-04-25',
        'amount' => '999.00',
    ]);

    $result = app(ReservedCostCalculator::class)->calculateForBudgetWindow(
        userId: $user->id,
        budgetWindowStart: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetWindowEnd: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
    );

    expect($result)->toBe('150.00');
});

it('sums future weekly occurrences inside the current monthly budget window', function () {
    $user = User::factory()->create();

    foreach ([
        ['2026-03-04', '100.00'],
        ['2026-03-11', '100.00'],
        ['2026-03-18', '100.00'],
        ['2026-03-25', '100.00'],
    ] as [$dueDate, $amount]) {
        FixedCostOccurrence::factory()->create([
            'user_id' => $user->id,
            'status' => FixedCostOccurenceStatus::PENDING->value,
            'due_date' => $dueDate,
            'amount' => $amount,
        ]);
    }

    $result = app(ReservedCostCalculator::class)
        ->calculateForBudgetWindow(
            userId: $user->id,
            budgetWindowStart: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
            budgetWindowEnd: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
        );

    expect($result)->toBe('400.00');
});

it('includes reservable status even if due date has passed (overdue)', function () {
    $user = User::factory()->create();

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => '2026-03',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-19',
        'amount' => '100.00',
    ]);

    $result = app(ReservedCostCalculator::class)->calculateForBudgetWindow(
        userId: $user->id,
        budgetWindowStart: CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'),
        budgetWindowEnd: CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta'),
    );

    expect($result)->toBe('100.00');
});
