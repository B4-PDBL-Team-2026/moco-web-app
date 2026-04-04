<?php

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Enums\CycleType;
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
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $mock = Mockery::mock(RecalculateBudgetSnapshotAction::class);
    $mock->shouldReceive('execute')->andReturn(new UserBudgetSnapshot);
    app()->instance(RecalculateBudgetSnapshotAction::class, $mock);
});

function cancelSetup(string $status = 'paid', ?string $dueDate = null): array
{
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

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
        'category_id' => $category->id,
    ]);

    $occurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-03',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_date' => $dueDate ?? '2026-03-15',
        'status' => $status,
        'amount' => '150000.00',
        'name' => 'Electricity',
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
        'paid_at' => $status === 'paid' ? now() : null,
    ]);

    return [$user, $occurrence];
}

test('unauthenticated cancel request returns 401', function () {
    $this->postJson('/api/fixed-costs/occurrences/1/cancel')->assertUnauthorized();
});

test('cancels a pending occurrence', function () {
    [$user, $occurrence] = cancelSetup('pending');

    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertOk();

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::SKIPPED);
});

test('cancels an overdue occurrence and change status to skipped', function () {
    [$user, $occurrence] = cancelSetup('overdue');
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertOk();

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::SKIPPED);
});

test('soft-deletes paid fixed cost linked transaction on cancel', function () {
    [$user, $occurrence] = cancelSetup();

    $tx = Transaction::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_occurrence_id' => $occurrence->id,
        'type' => TransactionType::EXPENSE->value,
        'source' => TransactionSource::FIXED_COST_PAYMENT->value,
        'name' => 'Electricity',
        'amount' => '150000.00',
        'transaction_at' => now()->toDateString(),
        'category_type' => $occurrence->category_type,
        'category_id' => $occurrence->category_id,
    ]);

    Sanctum::actingAs($user);
    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertOk();

    $this->assertSoftDeleted('transactions', ['id' => $tx->id]);
});

test('cancel returns 404 when occurrence is already void', function () {
    [$user, $occurrence] = cancelSetup('void');
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertNotFound();
});

test('cancels a paid occurrence and restores status to PENDING if due date is in the future', function () {
    CarbonImmutable::setTestNow('2026-03-20 10:00:00');

    [$user, $occurrence] = cancelSetup('paid', '2026-03-25');
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertOk();

    $fresh = $occurrence->fresh();
    expect($fresh->status)->toBe(FixedCostOccurenceStatus::PENDING)
        ->and($fresh->paid_at)->toBeNull();
});

test('cancels a paid occurrence and restores status to OVERDUE if due date is in the past', function () {
    CarbonImmutable::setTestNow('2026-03-20 10:00:00');

    [$user, $occurrence] = cancelSetup('paid', '2026-03-15');
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertOk();

    $fresh = $occurrence->fresh();
    expect($fresh->status)->toBe(FixedCostOccurenceStatus::OVERDUE)
        ->and($fresh->paid_at)->toBeNull();
});

test('cancel returns 404 when occurrence belongs to another user', function () {
    [$_, $occurrence] = cancelSetup('paid');
    $other = User::factory()->create();
    Sanctum::actingAs($other);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertNotFound();
});

test('cancel returns 404 when occurrence is already SKIPPED', function () {
    [$user, $occurrence] = cancelSetup('skipped');
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertNotFound();
});
