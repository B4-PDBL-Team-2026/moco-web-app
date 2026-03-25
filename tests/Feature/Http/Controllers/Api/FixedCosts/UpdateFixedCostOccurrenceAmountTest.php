<?php

use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

function amountSetup(string $status = 'void'): array
{
    $user = User::factory()->create();
    $cat = SystemCategory::factory()->create();

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
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

    $occ = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'due_date' => '2026-03-15',
        'status' => $status,
        'amount' => '150000.00',
        'name' => 'Gym',
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
        'voided_at' => $status === 'void' ? now() : null,
    ]);

    return [$user, $occ];
}

test('unauthenticated request returns 401', function () {
    $this->patchJson('/api/fixed-costs/occurrences/1/amount', [])->assertUnauthorized();
});

test('returns 422 when amount is missing', function () {
    [$user, $occ] = amountSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount'], 'data');
});

test('returns 422 when amount is zero', function () {
    [$user, $occ] = amountSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", ['amount' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount'], 'data');
});

test('returns 422 when amount is negative', function () {
    [$user, $occ] = amountSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", ['amount' => -100])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount'], 'data');
});

test('updates amount of void occurrence and returns 200', function () {
    [$user, $occ] = amountSetup('void');
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", ['amount' => 200000])
        ->assertOk();

    expect((string) $occ->fresh()->amount)->toBe('200000.00');
});

test('occurrence remains void after amount update (awaiting re-confirm)', function () {
    [$user, $occ] = amountSetup('void');
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", ['amount' => 200000])
        ->assertOk();

    expect($occ->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

test('returns 404 when occurrence is paid (must cancel first)', function () {
    [$user, $occ] = amountSetup('paid');
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", ['amount' => 200000])
        ->assertNotFound();
});

test('returns 404 when occurrence is pending', function () {
    [$user, $occ] = amountSetup('pending');
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", ['amount' => 200000])
        ->assertNotFound();
});

test('returns 404 when occurrence belongs to another user', function () {
    [$_, $occ] = amountSetup('void');
    $other = User::factory()->create();
    Sanctum::actingAs($other);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", ['amount' => 200000])
        ->assertNotFound();
});

test('returns 404 for non-existent occurrence', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/fixed-costs/occurrences/99999/amount', ['amount' => 200000])
        ->assertNotFound();
});
