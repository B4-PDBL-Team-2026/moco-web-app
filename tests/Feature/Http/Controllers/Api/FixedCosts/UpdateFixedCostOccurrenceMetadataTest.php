<?php

use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated request returns 401', function () {
    $this->patchJson('/api/fixed-costs/occurrences/1/amount', [])->assertUnauthorized();
});

test(' when amount is missing', function () {
    [$user, $occurrence] = amountSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occurrence->id}/amount", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test(' when amount is zero', function () {
    [$user, $occurrence] = amountSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occurrence->id}/amount", ['amount' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test(' when amount is negative', function () {
    [$user, $occurrence] = amountSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occurrence->id}/amount", ['amount' => -100])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
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

    $transaction = Transaction::factory()->createQuietly([
        'user_id' => $user->id,
        'fixed_cost_occurrence_id' => $occ->id,
        'type' => TransactionType::EXPENSE->value,
        'source' => TransactionSource::FIXED_COST_PAYMENT->value,
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
