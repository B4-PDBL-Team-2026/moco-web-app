<?php

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseHas;

it('recalculates user budget status correctly', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'));
    [$user] = setupUserWithBudget([
        'flooring_limit' => '10.00',
        'ceiling_limit' => '15.00',
    ]);

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => '2026-03',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-25',
        'amount' => '400.00',
    ]); // leftover balance: 600.00

    $status = app(RecalculateBudgetSnapshotAction::class)->execute(
        $user->id,
        now('Asia/Jakarta')->toImmutable()
    );

    expect((string) $status->current_balance)->toBe('1000.00')
        ->and((string) $status->reserved_cost)->toBe('400.00')
        ->and((string) $status->remaining_daily_allowance)->toBe('15.00')
        ->and((string) $status->raw_daily_allowance)->toBe('19.35')
        ->and((string) $status->daily_allowance_limit)->toBe('15.00')
        ->and($status->current_cycle_key)->toBe('2026-03')
        ->and($status->remaining_days)->toBe(31);
});

it('stores flooring as daily allowance and zero as actual daily allowance when reserved reaches balance', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'));
    [$user] = setupUserWithBudget([
        'initial_balance' => '100.00',
        'flooring_limit' => '10.00',
        'ceiling_limit' => '15.00',
    ]); // initial balance: 100.00

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => '2026-03',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-25',
        'amount' => '100.00',
    ]); // leftover balance: 0.00

    $status = app(RecalculateBudgetSnapshotAction::class)->execute(
        $user->id,
        now()->toImmutable(),
    );

    // 0.00 / 31 days left
    expect((string) $status->remaining_daily_allowance)->toBe('10.00')
        ->and((string) $status->raw_daily_allowance)->toBe('0.00')
        ->and((string) $status->daily_allowance_limit)->toBe('10.00');
});

it('stores flooring as displayed daily allowance when raw is below flooring', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'));
    [$user] = setupUserWithBudget([
        'initial_balance' => '100.00',
        'flooring_limit' => '10.00',
        'ceiling_limit' => '15.00',
    ]);

    $status = app(RecalculateBudgetSnapshotAction::class)->execute(
        $user->id,
        now('Asia/Jakarta')->toImmutable(),
    );

    expect((string) $status->remaining_daily_allowance)->toBe('10.00')
        ->and((string) $status->raw_daily_allowance)->toBe('3.22')
        ->and((string) $status->daily_allowance_limit)->toBe('10.00');
});

it('stores ceiling capped daily allowance and uncapped actual daily allowance', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'));
    [$user] = setupUserWithBudget([
        'initial_balance' => '1000.00',
        'flooring_limit' => '10.00',
        'ceiling_limit' => '15.00',
    ]);

    $status = app(RecalculateBudgetSnapshotAction::class)->execute(
        $user->id,
        now('Asia/Jakarta')->toImmutable()
    );

    expect((string) $status->remaining_daily_allowance)->toBe('15.00')
        ->and((string) $status->raw_daily_allowance)->toBe('32.25')
        ->and((string) $status->daily_allowance_limit)->toBe('15.00');
});

it('ignores unknown transaction types when calculating balance', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta'));
    [$user, $category] = setupUserWithBudget();

    DB::table('transactions')->insert([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => 'UNKNOWN_TYPE',
        'name' => 'unknown transaction type',
        'amount' => '500.00',
        'transaction_at' => '2026-03-20',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $status = app(RecalculateBudgetSnapshotAction::class)->execute(
        $user->id,
        now('Asia/Jakarta')->toImmutable()
    );

    expect((string) $status->current_balance)->toBe('1000.00');
    assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => 'UNKNOWN_TYPE',
    ]);
});

it('does not update daily_allowance_limit when recalculated on the same day', function () {
    $morningTime = CarbonImmutable::parse('2026-03-20 08:00:00', 'Asia/Jakarta');

    // Use the new helper completely to seed both Settings and Snapshot atomically
    [$user] = setupUserWithBudget([
        'initial_balance' => '1000.00',
        'reserved_cost' => '0.00',
        'remaining_daily_allowance' => '100.00',
        'daily_allowance_limit' => '100.00',
        'raw_daily_allowance' => '100.00',
        'recalculated_at' => $morningTime->utc(),
    ]);

    // Transaction entry created
    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => Category::factory()->create()->id,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '400.00', // Remaining balance should be 600
        'name' => 'Jajan Siang',
        'transaction_at' => '2026-03-20',
    ]);

    // Force call recalculate snapshot on the same day
    $afternoonTime = CarbonImmutable::parse('2026-03-20 14:00:00', 'Asia/Jakarta');
    $status = app(RecalculateBudgetSnapshotAction::class)->execute($user->id, $afternoonTime);

    // Limit must be fixed since it's the same day
    expect((string) $status->current_balance)->toBe('600.00')
        ->and((string) $status->remaining_daily_allowance)->toBe('50.00') // 600 / 12 days left
        ->and((string) $status->daily_allowance_limit)->toBe('100.00');
});

it('updates daily_allowance_limit when recalculated on a new day', function () {
    // Last snapshot time
    $yesterdayTime = CarbonImmutable::parse('2026-03-20 23:00:00', 'Asia/Jakarta');

    [$user] = setupUserWithBudget([
        'initial_balance' => '1000.00',
        'reserved_cost' => '0.00',
        'remaining_daily_allowance' => '100.00',
        'daily_allowance_limit' => '100.00',
        'raw_daily_allowance' => '100.00',
        'recalculated_at' => $yesterdayTime->utc(),
    ]);

    // Expense transaction yesterday that should be included in next day allowance calculation
    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => Category::factory()->create()->id,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '450.00',
        'name' => 'Belanja Bulanan',
        'transaction_at' => '2026-03-20',
    ]);

    $todayTime = CarbonImmutable::parse('2026-03-21 07:00:00', 'Asia/Jakarta');
    $status = app(RecalculateBudgetSnapshotAction::class)->execute($user->id, $todayTime);

    expect((string) $status->current_balance)->toBe('550.00')
        ->and((string) $status->remaining_daily_allowance)->toBe('50.00')
        ->and((string) $status->daily_allowance_limit)->toBe('50.00')
        ->and($status->remaining_days)->toBe(11);
});

it('determines new day strictly based on user timezone, not UTC server time', function () {
    $lateNightJakarta = CarbonImmutable::parse('2026-03-20 23:00:00', 'Asia/Jakarta');

    [$user] = setupUserWithBudget([
        'initial_balance' => '1000.00',
        'reserved_cost' => '0.00',
        'remaining_daily_allowance' => '100.00',
        'daily_allowance_limit' => '100.00',
        'raw_daily_allowance' => '100.00',
        'recalculated_at' => $lateNightJakarta->utc(),
    ]);

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => Category::factory()->create()->id,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '450.00',
        'name' => 'Midnight Snack',
        'transaction_at' => '2026-03-20',
    ]);

    // 21 March 01:00 AM Asia/Jakarta (20 March 06:00 PM UTC)
    $earlyMorningJakarta = CarbonImmutable::parse('2026-03-21 01:00:00', 'Asia/Jakarta');

    $status = app(RecalculateBudgetSnapshotAction::class)->execute($user->id, $earlyMorningJakarta);

    expect((string) $status->daily_allowance_limit)->toBe('50.00')
        ->and($status->remaining_days)->toBe(11);
});
