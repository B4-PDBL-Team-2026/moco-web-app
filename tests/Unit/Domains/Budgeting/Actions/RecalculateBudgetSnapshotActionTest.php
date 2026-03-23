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
        ->and((string) $status->daily_allowance)->toBe('50.00')
        ->and((string) $status->actual_daily_allowance)->toBe('50.00')
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

    expect((string) $status->daily_allowance)->toBe('20.00')
        ->and((string) $status->actual_daily_allowance)->toBe('0.00');
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

    expect((string) $status->daily_allowance)->toBe('50.00')
        ->and((string) $status->actual_daily_allowance)->toBe('8.33');
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

    expect((string) $status->daily_allowance)->toBe('30.00')
        ->and((string) $status->actual_daily_allowance)->toBe('83.33');
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
