<?php

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\Category;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\User;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $mock = Mockery::mock(RecalculateBudgetSnapshotAction::class);
    $mock->shouldReceive('execute')->andReturn(new UserBudgetSnapshot);
    app()->instance(RecalculateBudgetSnapshotAction::class, $mock);
});

function confirmSetup(string $balance = '500000.00', string $status = 'pending'): array
{
    $user = User::factory()->create();
    $category = Category::factory()->expense()->create();

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
        'name' => 'Netflix',
        'category_id' => $category->id,
    ]);

    return [$user, $occurrence];
}

test('unauthenticated request returns 401', function () {
    $this->postJson('/api/fixed-costs/occurrences/1/confirm')->assertUnauthorized();
});

test('confirms a pending occurrence and returns 200', function () {
    [$user, $occurrence] = confirmSetup();
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/confirm")->assertOk();

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::PAID)
        ->and($occurrence->fresh()->paid_at)->not->toBeNull();
});

test('confirms an overdue occurrence', function () {
    [$user, $occurrence] = confirmSetup('500000.00', 'overdue');
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/confirm")->assertOk();

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::PAID);
});

test('creates a linked expense transaction on confirmation', function () {
    [$user, $occurrence] = confirmSetup();
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/confirm")->assertOk();

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'fixed_cost_occurrence_id' => $occurrence->id,
        'type' => 'expense',
        'source' => 'fixed_cost_payment',
        'amount' => '150000.00',
    ]);
});

test('returns error when balance is less than occurrence amount (BR §13)', function () {
    [$user, $occurrence] = confirmSetup('100000.00'); // balance < 150k
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/confirm")
        ->assertStatus(500);

    expect($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::PENDING);
    $this->assertDatabaseMissing('transactions', ['fixed_cost_occurrence_id' => $occurrence->id]);
});

test('returns 404 when occurrence belongs to another user', function () {
    [$_, $occurrence] = confirmSetup();
    $otherUser = User::factory()->create();
    UserBudgetSnapshot::factory()->create(['user_id' => $otherUser->id, 'current_balance' => '999999.00', 'current_cycle_key' => '2026-03', 'cycle_start_date' => '2026-03-01', 'cycle_end_date' => '2026-03-31', 'remaining_days' => 10]);
    Sanctum::actingAs($otherUser);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/confirm")->assertNotFound();
});

test('returns 404 when occurrence is already paid', function () {
    [$user, $occurrence] = confirmSetup();
    $occurrence->update(['status' => FixedCostOccurenceStatus::PAID->value]);
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/confirm")->assertNotFound();
});

test('returns 404 when occurrence is void', function () {
    [$user, $occurrence] = confirmSetup();
    $occurrence->update(['status' => FixedCostOccurenceStatus::VOID->value]);
    Sanctum::actingAs($user);

    $this->postJson("/api/fixed-costs/occurrences/{$occurrence->id}/confirm")->assertNotFound();
});

test('returns 404 for a non-existent occurrence id', function () {
    $user = User::factory()->create();
    UserBudgetSnapshot::factory()->create(['user_id' => $user->id, 'current_balance' => '999999.00', 'current_cycle_key' => '2026-03', 'cycle_start_date' => '2026-03-01', 'cycle_end_date' => '2026-03-31', 'remaining_days' => 10]);
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs/occurrences/99999/confirm')->assertNotFound();
});
