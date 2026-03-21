<?php

use App\Domains\Budgeting\Services\ReservedCostCalculatorService;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

    $result = app(ReservedCostCalculatorService::class)->calculateForCurrentCycle(
        userId: $user->id,
        cycleKey: '2026-03',
        today: CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'),
    );

    expect($result)->toBe('150.00');
});

it('ignores reservable status with due date before today', function () {
    $user = User::factory()->create();

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => '2026-03',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-19',
        'amount' => '100.00',
    ]);

    $result = app(ReservedCostCalculatorService::class)->calculateForCurrentCycle(
        userId: $user->id,
        cycleKey: '2026-03',
        today: CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'),
    );

    expect($result)->toBe('0.00');
});
