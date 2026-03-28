<?php

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

function amountSetup(string $status = 'void', string $balance = '500000.00'): array
{
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '0.00',
        'flooring_limit' => '0.00',
        'ceiling_limit' => '999999.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 10,
        'current_balance' => $balance,
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
        'name' => 'Gym',
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
        'voided_at' => $status === 'void' ? now() : null,
    ]);

    return [$user, $occurrence, $category];
}

test('unauthenticated request returns 401', function () {
    $this->patchJson('/api/fixed-costs/occurrences/1/amount', [])->assertUnauthorized();
});

test('returns 422 when amount is missing', function () {
    [$user, $occurrence] = amountSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occurrence->id}/amount", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount'], 'data');
});

test('returns 422 when amount is zero', function () {
    [$user, $occurrence] = amountSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occurrence->id}/amount", ['amount' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount'], 'data');
});

test('returns 422 when amount is negative', function () {
    [$user, $occurrence] = amountSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occurrence->id}/amount", ['amount' => -100])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount'], 'data');
});

test('updates amount of PENDING occurrence and returns 200', function () {
    [$user, $occurrence] = amountSetup(FixedCostOccurenceStatus::PENDING->value);
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occurrence->id}/amount", ['amount' => 200000])
        ->assertOk();

    expect((string) $occurrence->fresh()->amount)->toBe('200000.00');
});

test('updates amount of OVERDUE occurrence and returns 200', function () {
    [$user, $occurrence] = amountSetup(FixedCostOccurenceStatus::OVERDUE->value);
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occurrence->id}/amount", ['amount' => 250000])
        ->assertOk();

    expect((string) $occurrence->fresh()->amount)->toBe('250000.00');
});

test('updates amount of PAID occurrence, syncs transaction, and returns 200', function () {
    [$user, $occ, $cat] = amountSetup(FixedCostOccurenceStatus::PAID->value);

    // attach transaction to sync
    $transaction = Transaction::factory()->createQuietly([
        'user_id' => $user->id,
        'fixed_cost_occurrence_id' => $occ->id,
        'type' => TransactionType::EXPENSE->value,
        'source' => TransactionSource::FIXED_COST_PAYMENT->value,
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
        'amount' => '150000.00',
    ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", ['amount' => 200000])
        ->assertOk();

    expect((string) $occ->fresh()->amount)->toBe('200000.00')
        ->and((string) $transaction->fresh()->amount)->toBe('200000.00');
});

test('returns error when increasing a PAID occurrence exceeds balance', function () {
    [$user, $occ] = amountSetup(FixedCostOccurenceStatus::PAID->value, '50000.00');
    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", ['amount' => 300000]);

    $response->assertStatus(422);
});

test('returns 404 when occurrence is VOID', function () {
    [$user, $occ] = amountSetup(FixedCostOccurenceStatus::VOID->value);
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/amount", ['amount' => 200000])
        ->assertNotFound();
});

test('returns 404 when occurrence belongs to another user', function () {
    [$_, $occurrence] = amountSetup('void');
    $other = User::factory()->create();
    Sanctum::actingAs($other);

    $this->patchJson("/api/fixed-costs/occurrences/{$occurrence->id}/amount", ['amount' => 200000])
        ->assertNotFound();
});

test('returns 404 for non-existent occurrence', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/fixed-costs/occurrences/99999/amount', ['amount' => 200000])
        ->assertNotFound();
});
