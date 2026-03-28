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
use Carbon\CarbonImmutable;

function setupForCancel(string $status = 'paid', ?string $dueDateStr = null): array
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

    $occurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'due_date' => $dueDateStr ?? '2026-03-15',
        'status' => $status,
        'amount' => '150000.00',
        'name' => 'Electricity',
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
        'paid_at' => now(),
    ]);

    return [$user, $occurrence, $cat];
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

it('cancels a PENDING occurrence by setting it to SKIPPED', function () {
    [$user, $occurrence] = setupForCancel('pending');

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::SKIPPED);
});

it('cancels an OVERDUE occurrence by setting it to SKIPPED', function () {
    [$user, $occurrence] = setupForCancel('overdue');

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::SKIPPED);
});

it('cancels a PAID occurrence and restores it to PENDING if due date is in the future', function () {
    CarbonImmutable::setTestNow('2026-03-20 10:00:00');

    // Due date 25 (future)
    [$user, $occurrence] = setupForCancel('paid', '2026-03-25');

    $this->action->execute($user->id, $occurrence->id);

    $fresh = $occurrence->fresh();
    expect($fresh->status)->toBe(FixedCostOccurenceStatus::PENDING)
        ->and($fresh->paid_at)->toBeNull();
});

it('cancels a PAID occurrence and restores it to OVERDUE if due date is in the past', function () {
    CarbonImmutable::setTestNow('2026-03-20 10:00:00');

    // Due date 15 (past)
    [$user, $occurrence] = setupForCancel('paid', '2026-03-15');

    $this->action->execute($user->id, $occurrence->id);

    $fresh = $occurrence->fresh();
    expect($fresh->status)->toBe(FixedCostOccurenceStatus::OVERDUE)
        ->and($fresh->paid_at)->toBeNull();
});

it('soft-deletes the linked expense transaction when reverting a paid occurrence', function () {
    [$user, $occurrence] = setupForCancel('paid');
    $transaction = attachTransaction($user, $occurrence);

    $this->action->execute($user->id, $occurrence->id);

    $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
});

// --- SCENARIO 3: GENERAL BEHAVIORS & EXCEPTIONS ---

it('calls RecalculateBudgetSnapshotAction after cancellation', function () {
    [$user, $occurrence] = setupForCancel('paid');

    $this->action->execute($user->id, $occurrence->id);

    $this->mockRecalculate->shouldHaveReceived('execute')->once()->with($user->id);
});

it('works correctly even when no linked transaction exists on a paid occurrence', function () {
    CarbonImmutable::setTestNow('2026-03-23 10:00:00');
    [$user, $occurrence] = setupForCancel('paid', '2026-03-25');
    // No transaction attached

    $this->action->execute($user->id, $occurrence->id);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::PENDING); // Because it's a future due date
});

it('throws ModelNotFoundException when occurrence is already VOID', function () {
    [$user, $occurrence] = setupForCancel('void');

    expect(fn () => $this->action->execute($user->id, $occurrence->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException when occurrence is already SKIPPED', function () {
    [$user, $occurrence] = setupForCancel('skipped');

    expect(fn () => $this->action->execute($user->id, $occurrence->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException when occurrence belongs to another user', function () {
    [$_, $occurrence] = setupForCancel('paid');
    $otherUser = User::factory()->create();

    expect(fn () => $this->action->execute($otherUser->id, $occurrence->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException for non-existent occurrence id', function () {
    $user = User::factory()->create();

    expect(fn () => $this->action->execute($user->id, 99999))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
