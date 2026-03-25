<?php

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\FixedCosts\Actions\CancelFixedCostPaymentAction;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSnapshot;

function setupForCancel(): array
{
    $user = User::factory()->create();
    $cat = SystemCategory::factory()->create();

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '500000.00',
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
        'status' => FixedCostOccurenceStatus::PAID->value,
        'amount' => '150000.00',
        'name' => 'Electricity',
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
        'paid_at' => now(),
    ]);

    return [$user, $occurrenceurrence];
}

function attachTransaction(User $user, FixedCostOccurrence $occurrence): Transaction
{
    return Transaction::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_occurrence_id' => $occurrence->id,
        'type' => TransactionType::EXPENSE->value,
        'source' => TransactionSource::FIXED_COST_PAYMENT->value,
        'name' => 'Electricity',
        'amount' => '150000.00',
        'category_id' => $occurrence->category_id,
        'category_type' => SystemCategory::class,
        'transaction_date' => now()->toDateString(),
    ]);
}

beforeEach(function () {
    $this->mockRecalculate = Mockery::mock(RecalculateBudgetSnapshotAction::class);

    $this->mockRecalculate->shouldReceive('execute')->andReturn(new UserBudgetSnapshot);

    app()->instance(RecalculateBudgetSnapshotAction::class, $this->mockRecalculate);

    $this->action = app(CancelFixedCostPaymentAction::class);
});

it('sets occurrence status to VOID', function () {
    [$user, $occurrence] = setupForCancel();

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

it('sets voided_at timestamp', function () {
    [$user, $occurrence] = setupForCancel();

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->voided_at)->not->toBeNull();
});

it('clears paid_at on cancellation', function () {
    [$user, $occurrence] = setupForCancel();

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->paid_at)->toBeNull();
});

it('cancels a PENDING occurrence', function () {
    [$user, $occurrence] = setupForCancel();
    $occurrence->update(['status' => FixedCostOccurenceStatus::PENDING->value, 'paid_at' => null]);

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

it('cancels an OVERDUE occurrence', function () {
    [$user, $occurrence] = setupForCancel();
    $occurrence->update(['status' => FixedCostOccurenceStatus::OVERDUE->value, 'paid_at' => null]);

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

it('soft-deletes the linked expense transaction', function () {
    [$user, $occurrence] = setupForCancel();
    $transaction = attachTransaction($user, $occurrence);

    $this->action->execute($user->id, $occurrence->id);

    $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
});

it('works correctly even when no linked transaction exists', function () {
    [$user, $occurrence] = setupForCancel();
    // No transaction attached — action should still succeed

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

it('calls RecalculateBudgetSnapshotAction after cancellation', function () {
    [$user, $occurrence] = setupForCancel();

    $this->action->execute($user->id, $occurrence->id);

    $this->mockRecalculate->shouldHaveReceived('execute')->once()->with($user->id);
});

it('cancels a paid occurrence from a previous cycle', function () {
    [$user, $occurrence] = setupForCancel();
    $occurrence->update(['cycle_key' => '2026-02']);

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

it('throws ModelNotFoundException when occurrence is already VOID', function () {
    [$user, $occurrence] = setupForCancel();
    $occurrence->update(['status' => FixedCostOccurenceStatus::VOID->value]);

    expect(fn () => $this->action->execute($user->id, $occurrence->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException when occurrence belongs to another user', function () {
    [$_, $occurrence] = setupForCancel();
    $otherUser = User::factory()->create();

    expect(fn () => $this->action->execute($otherUser->id, $occurrence->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException for non-existent occurrence id', function () {
    $user = User::factory()->create();

    expect(fn () => $this->action->execute($user->id, 99999))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
