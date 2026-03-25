<?php

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $mock = Mockery::mock(RecalculateBudgetSnapshotAction::class);
    $mock->shouldReceive('execute')->andReturn(new UserBudgetSnapshot);
    app()->instance(RecalculateBudgetSnapshotAction::class, $mock);
});

function cancelSetup(string $status = 'paid'): array
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
        'cycle_type' => 'monthly',
        'due_date' => '2026-03-15',
        'status' => $status,
        'amount' => '150000.00',
        'name' => 'Electricity',
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
        'paid_at' => $status === 'paid' ? now() : null,
    ]);

    return [$user, $occurrence, $category];
}

test('unauthenticated cancel request returns 401', function () {
    $this->postJson('/api/fixed-costs/occurrences/1/cancel')->assertUnauthorized();
});

test('cancels a paid occurrence and sets status to void', function () {
    [$user, $occurrence] = cancelSetup('paid');
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertOk();

    $fresh = $occurrence->fresh();
    expect($fresh->status)->toBe(FixedCostOccurenceStatus::VOID)
        ->and($fresh->paid_at)->toBeNull()
        ->and($fresh->voided_at)->not->toBeNull();
});

test('cancels a pending occurrence', function () {
    [$user, $occurrence] = cancelSetup('pending');
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertOk();

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

test('cancels an overdue occurrence', function () {
    [$user, $occurrence] = cancelSetup('overdue');
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertOk();

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

test('soft-deletes the linked transaction on cancel', function () {
    [$user, $occurrence] = cancelSetup('paid');

    $tx = Transaction::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_occurrence_id' => $occurrence->id,
        'type' => TransactionType::EXPENSE->value,
        'source' => TransactionSource::FIXED_COST_PAYMENT->value,
        'name' => 'Electricity',
        'amount' => '150000.00',
        'transaction_date' => now()->toDateString(),
        'category_type' => $occurrence->category_type,
        'category_id' => $occurrence->category_id,
    ]);

    Sanctum::actingAs($user);
    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertOk();

    $this->assertSoftDeleted('transactions', ['id' => $tx->id]);
});

test('cancel returns 404 when occurrence belongs to another user', function () {
    [$_, $occurrence] = cancelSetup('paid');
    $other = User::factory()->create();
    Sanctum::actingAs($other);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertNotFound();
});

test('cancel returns 404 when occurrence is already void', function () {
    [$user, $occurrence] = cancelSetup('void');
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/cancel")->assertNotFound();
});
