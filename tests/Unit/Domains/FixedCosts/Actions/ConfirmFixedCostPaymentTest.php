<?php

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\FixedCosts\Actions\ConfirmFixedCostPaymentAction;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;
use App\Models\UserBudgetSnapshot;

function setupForConfirm(string $balance = '500000.00'): array
{
    $user = User::factory()->create();
    $cat = SystemCategory::factory()->create();

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => $balance,
        'reserved_cost' => '150000.00',
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 10,
    ]);

    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
    ]);

    $occurrenceurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'due_date' => '2026-03-15',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'amount' => '150000.00',
        'name' => 'Netflix',
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
    ]);

    return [$user, $occurrenceurrence, $cat];
}

beforeEach(function () {
    $this->mockRecalculate = Mockery::mock(RecalculateBudgetSnapshotAction::class);

    $this->mockRecalculate->shouldReceive('execute')
        ->andReturn(new UserBudgetSnapshot);

    app()->instance(RecalculateBudgetSnapshotAction::class, $this->mockRecalculate);

    $this->action = app(ConfirmFixedCostPaymentAction::class);
});

it('sets occurrence status to PAID', function () {
    [$user, $occurrence] = setupForConfirm();

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::PAID);
});

it('sets paid_at timestamp on confirmation', function () {
    [$user, $occurrence] = setupForConfirm();

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->paid_at)->not->toBeNull();
});

it('confirms an OVERDUE occurrence', function () {
    [$user, $occurrence] = setupForConfirm();
    $occurrence->update(['status' => FixedCostOccurenceStatus::OVERDUE->value]);

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::PAID);
});

it('creates a linked expense transaction with correct fields', function () {
    [$user, $occurrence, $cat] = setupForConfirm();

    $this->action->execute($user->id, $occurrence->id);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'fixed_cost_occurrence_id' => $occurrence->id,
        'type' => TransactionType::EXPENSE->value,
        'source' => TransactionSource::FIXED_COST_PAYMENT->value,
        'name' => 'Netflix',
        'amount' => '150000.00',
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
    ]);
});

it('calls RecalculateBudgetSnapshotAction after confirming (BR §14)', function () {
    [$user, $occurrence] = setupForConfirm();

    $this->action->execute($user->id, $occurrence->id);

    $this->mockRecalculate->shouldHaveReceived('execute')
        ->once()
        ->with($user->id);
});

it('rejects confirmation when balance is less than occurrence amount (BR §13)', function () {
    // Balance 100k < occurrence 150k
    [$user, $occurrence] = setupForConfirm('100000.00');

    expect(fn () => $this->action->execute($user->id, $occurrence->id))
        ->toThrow(InvalidArgumentException::class, 'Insufficient balance');
});

it('allows confirmation when balance exactly equals occurrence amount', function () {
    [$user, $occurrence] = setupForConfirm('150000.00');

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::PAID);
});

it('does not create a transaction when balance check fails', function () {
    [$user, $occurrence] = setupForConfirm('100000.00');

    try {
        $this->action->execute($user->id, $occurrence->id);
    } catch (InvalidArgumentException) {
    }

    $this->assertDatabaseMissing('transactions', ['fixed_cost_occurrence_id' => $occurrence->id]);
});

it('throws ModelNotFoundException when occurrence belongs to another user', function () {
    [$_, $occurrence] = setupForConfirm();
    $otherUser = User::factory()->create();

    UserBudgetSnapshot::factory()->create([
        'user_id' => $otherUser->id,
        'current_balance' => '999999.00',
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 10,
    ]);

    expect(fn () => $this->action->execute($otherUser->id, $occurrence->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException when trying to confirm an already PAID occurrence', function () {
    [$user, $occurrence] = setupForConfirm();
    $occurrence->update(['status' => FixedCostOccurenceStatus::PAID->value]);

    expect(fn () => $this->action->execute($user->id, $occurrence->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException when trying to confirm a VOID occurrence', function () {
    [$user, $occurrence] = setupForConfirm();
    $occurrence->update(['status' => FixedCostOccurenceStatus::VOID->value]);

    expect(fn () => $this->action->execute($user->id, $occurrence->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException for a non-existent occurrence id', function () {
    [$user] = setupForConfirm();

    expect(fn () => $this->action->execute($user->id, 99999))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
