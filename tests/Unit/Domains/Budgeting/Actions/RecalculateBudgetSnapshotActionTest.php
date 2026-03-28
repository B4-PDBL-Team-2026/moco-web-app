 <?php

 use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Enums\CycleType;
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

use function Pest\Laravel\assertDatabaseHas;

it('recalculates user budget status correctly', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '1000.00',
        'flooring_limit' => '0.00',
        'ceiling_limit' => '999999.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'fixed_cost_occurrence_id' => null,
        'type' => TransactionType::INCOME->value,
        'source' => TransactionSource::INITIAL_BALANCE->value,
        'name' => 'initial balance',
        'amount' => '1000.00',
        'transaction_date' => '2026-03-20',
    ]);

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => '2026-03',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-25',
        'amount' => '400.00',
    ]);

    $status = app(RecalculateBudgetSnapshotAction::class)->execute(
        $user->id,
        CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta')
    );

    expect((string) $status->current_balance)->toBe('1000.00')
        ->and((string) $status->reserved_cost)->toBe('400.00')
        ->and((string) $status->remaining_daily_allowance)->toBe('50.00')
        ->and((string) $status->raw_daily_allowance)->toBe('50.00')
        ->and((string) $status->daily_allowance_limit)->toBe('50.00')
        ->and($status->current_cycle_key)->toBe('2026-03')
        ->and($status->remaining_days)->toBe(12);
});

it('stores flooring as daily allowance and zero as actual daily allowance when reserved reaches balance', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '100.00',
        'flooring_limit' => '20.00',
        'ceiling_limit' => '999999.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'fixed_cost_occurrence_id' => null,
        'type' => TransactionType::INCOME->value,
        'source' => TransactionSource::INITIAL_BALANCE->value,
        'name' => 'initial balance',
        'amount' => '100.00',
        'transaction_date' => '2026-03-20',
    ]);

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'cycle_key' => '2026-03',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'due_date' => '2026-03-25',
        'amount' => '100.00',
    ]);

    $status = app(RecalculateBudgetSnapshotAction::class)->execute(
        $user->id,
        CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta')
    );

    expect((string) $status->remaining_daily_allowance)->toBe('20.00')
        ->and((string) $status->raw_daily_allowance)->toBe('0.00')
        ->and((string) $status->daily_allowance_limit)->toBe('20.00');
});

it('stores flooring as displayed daily allowance when raw is below flooring', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '100.00',
        'flooring_limit' => '50.00',
        'ceiling_limit' => '999999.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'fixed_cost_occurrence_id' => null,
        'type' => TransactionType::INCOME->value,
        'source' => TransactionSource::INITIAL_BALANCE->value,
        'name' => 'initial balance',
        'amount' => '100.00',
        'transaction_date' => '2026-03-20',
    ]);

    $status = app(RecalculateBudgetSnapshotAction::class)->execute(
        $user->id,
        CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta')
    );

    expect((string) $status->remaining_daily_allowance)->toBe('50.00')
        ->and((string) $status->raw_daily_allowance)->toBe('8.33')
        ->and((string) $status->daily_allowance_limit)->toBe('50.00');
});

it('stores ceiling capped daily allowance and uncapped actual daily allowance', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '1000.00',
        'flooring_limit' => '0.00',
        'ceiling_limit' => '30.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'fixed_cost_occurrence_id' => null,
        'type' => TransactionType::INCOME->value,
        'source' => TransactionSource::INITIAL_BALANCE->value,
        'name' => 'initial balance',
        'amount' => '1000.00',
        'transaction_date' => '2026-03-20',
    ]);

    $status = app(RecalculateBudgetSnapshotAction::class)->execute(
        $user->id,
        CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta')
    );

    expect((string) $status->remaining_daily_allowance)->toBe('30.00')
        ->and((string) $status->raw_daily_allowance)->toBe('83.33')
        ->and((string) $status->daily_allowance_limit)->toBe('30.00');
});

it('ignores unknown transaction types when calculating balance', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '0.00',
        'flooring_limit' => '0.00',
        'ceiling_limit' => '50000.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::INCOME->value,
        'amount' => '1000.00',
        'name' => 'initial balance',
        'transaction_date' => '2026-03-20',
    ]);

    DB::table('transactions')->insert([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => 'UNKNOWN_TYPE',
        'name' => 'unknown transaction type',
        'amount' => '500.00',
        'transaction_date' => '2026-03-20',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $status = app(RecalculateBudgetSnapshotAction::class)->execute(
        $user->id,
        CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta')
    );

    expect((string) $status->current_balance)->toBe('1000.00');
    assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => 'UNKNOWN_TYPE',
    ]);
});

it('does not update daily_allowance_limit when recalculated on the same day', function () {
    $user = User::factory()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '1000.00',
        'flooring_limit' => '0.00',
        'ceiling_limit' => '999999.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    $morningTime = CarbonImmutable::parse('2026-03-20 08:00:00', 'Asia/Jakarta');

    // seed snapshot in the past
    UserBudgetSnapshot::query()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
        'reserved_cost' => '0.00',
        'remaining_daily_allowance' => '100.00',
        'daily_allowance_limit' => '100.00',
        'raw_daily_allowance' => '100.00',
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 12,
        'recalculated_at' => $morningTime,
    ]);

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => SystemCategory::factory()->create()->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::INCOME->value,
        'source' => TransactionSource::INITIAL_BALANCE->value,
        'amount' => '1000.00',
        'name' => 'Initial Balance',
        'transaction_date' => '2026-03-01',
    ]);

    // transaction entry created
    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => SystemCategory::factory()->create()->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '400.00', // Sisa uang harusnya tinggal 600
        'name' => 'Jajan Siang',
        'transaction_date' => '2026-03-20',
    ]);

    // force call recaltulate snapshot
    $afternoonTime = CarbonImmutable::parse('2026-03-20 14:00:00', 'Asia/Jakarta');
    $status = app(RecalculateBudgetSnapshotAction::class)->execute($user->id, $afternoonTime);

    // limit must be fixed
    expect((string) $status->current_balance)->toBe('600.00')
        ->and((string) $status->remaining_daily_allowance)->toBe('50.00') // 600 / 12 day left
        ->and((string) $status->daily_allowance_limit)->toBe('100.00');
});

it('updates daily_allowance_limit when recalculated on a new day', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '1000.00',
        'flooring_limit' => '0.00',
        'ceiling_limit' => '999999.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'fixed_cost_occurrence_id' => null,
        'type' => TransactionType::INCOME->value,
        'source' => TransactionSource::INITIAL_BALANCE->value,
        'name' => 'initial balance',
        'amount' => '1000.00',
        'transaction_date' => '2026-03-20',
    ]);

    // last snapshot time
    $yesterdayTime = CarbonImmutable::parse('2026-03-20 23:00:00', 'Asia/Jakarta');

    // fake snapshot budget
    UserBudgetSnapshot::query()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
        'reserved_cost' => '0.00',
        'remaining_daily_allowance' => '100.00',
        'daily_allowance_limit' => '100.00',
        'raw_daily_allowance' => '100.00',
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 12,
        'recalculated_at' => $yesterdayTime->utc(),
    ]);

    // expense transaction yesterday that should be included in next day allowance calculation
    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => SystemCategory::factory()->create()->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '450.00',
        'name' => 'Belanja Bulanan',
        'transaction_date' => '2026-03-20',
    ]);

    $todayTime = CarbonImmutable::parse('2026-03-21 07:00:00', 'Asia/Jakarta');
    $status = app(RecalculateBudgetSnapshotAction::class)->execute($user->id, $todayTime);

    expect((string) $status->current_balance)->toBe('550.00')
        ->and((string) $status->remaining_daily_allowance)->toBe('50.00')
        ->and((string) $status->daily_allowance_limit)->toBe('50.00')
        ->and($status->remaining_days)->toBe(11);
});

it('determines new day strictly based on user timezone, not UTC server time', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '1000.00',
        'flooring_limit' => '0.00',
        'ceiling_limit' => '999999.00',
        'timezone' => 'Asia/Jakarta', // GMT+7
    ]);

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'fixed_cost_occurrence_id' => null,
        'type' => TransactionType::INCOME->value,
        'source' => TransactionSource::INITIAL_BALANCE->value,
        'name' => 'initial balance',
        'amount' => '1000.00',
        'transaction_date' => '2026-03-20',
    ]);

    $lateNightJakarta = CarbonImmutable::parse('2026-03-20 23:00:00', 'Asia/Jakarta');

    UserBudgetSnapshot::query()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
        'reserved_cost' => '0.00',
        'remaining_daily_allowance' => '100.00',
        'daily_allowance_limit' => '100.00',
        'raw_daily_allowance' => '100.00',
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 12,
        'recalculated_at' => $lateNightJakarta->utc(),
    ]);

    Transaction::query()->create([
        'user_id' => $user->id,
        'category_id' => SystemCategory::factory()->create()->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
        'amount' => '450.00',
        'name' => 'Midnight Snack',
        'transaction_date' => '2026-03-20',
    ]);

    // 21 Maret 01:00 PM Asia/Jakarta, 20 Maret 06:00 PM UTC)
    $earlyMorningJakarta = CarbonImmutable::parse('2026-03-21 01:00:00', 'Asia/Jakarta');

    $status = app(RecalculateBudgetSnapshotAction::class)->execute($user->id, $earlyMorningJakarta);

    expect((string) $status->daily_allowance_limit)->toBe('50.00')
        ->and($status->remaining_days)->toBe(11);
});
