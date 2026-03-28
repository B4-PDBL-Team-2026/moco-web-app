<?php

use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

function metadataSetup(): array
{
    $user = User::factory()->create();
    $cat = SystemCategory::factory()->create();

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '500000.00',
        'reserved_cost' => '150000.00',
        'remaining_daily_allowance' => '30000.00',
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
        'status' => FixedCostOccurenceStatus::PAID->value,
        'amount' => '150000.00',
        'name' => 'Old Name',
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
        'paid_at' => now(),
    ]);

    return [$user, $occ];
}

test('unauthenticated request returns 401', function () {
    $this->patchJson('/api/fixed-costs/occurrences/1/metadata', [])->assertUnauthorized();
});

test('returns 422 when name is empty string', function () {
    [$user, $occ] = metadataSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/metadata", ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name'], 'data');
});

test('returns 422 when note exceeds max length', function () {
    [$user, $occ] = metadataSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/metadata", [
        'note' => str_repeat('x', 1001),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['note'], 'data');
});

test('updates name and returns 200', function () {
    [$user, $occ] = metadataSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/metadata", ['name' => 'New Name'])
        ->assertOk();

    expect($occ->fresh()->name)->toBe('New Name');
});

test('updates note and returns 200', function () {
    [$user, $occ] = metadataSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/metadata", ['note' => 'Paid via transfer'])
        ->assertOk();

    expect($occ->fresh()->note)->toBe('Paid via transfer');
});

test('does not change amount or status (BR §15)', function () {
    [$user, $occ] = metadataSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/metadata", ['name' => 'Updated'])
        ->assertOk();

    expect((string) $occ->fresh()->amount)->toBe('150000.00')
        ->and($occ->fresh()->status)->toBe(FixedCostOccurenceStatus::PAID);
});

test('snapshot is not recalculated on metadata update (BR §15)', function () {
    [$user, $occ] = metadataSetup();

    $snapshotBefore = App\Models\UserBudgetSnapshot::where('user_id', $user->id)->first();
    $balanceBefore = (string) $snapshotBefore->current_balance;

    Sanctum::actingAs($user);
    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/metadata", ['name' => 'Updated'])
        ->assertOk();

    expect((string) $snapshotBefore->fresh()->current_balance)->toBe($balanceBefore);
});

test('works on occurrences of any status', function () {
    foreach (['pending', 'overdue', 'paid', 'void'] as $status) {
        [$user, $occ] = metadataSetup();
        $occ->update(['status' => $status]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/metadata", [
            'name' => "Updated {$status}",
        ])->assertOk();

        expect($occ->fresh()->name)->toBe("Updated {$status}");
    }
});

test('returns 404 when occurrence belongs to another user', function () {
    [$_, $occ] = metadataSetup();
    $other = User::factory()->create();
    Sanctum::actingAs($other);

    $this->patchJson("/api/fixed-costs/occurrences/{$occ->id}/metadata", ['name' => 'Hack'])
        ->assertNotFound();
});

test('returns 404 for a non-existent occurrence id', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/fixed-costs/occurrences/99999/metadata', ['name' => 'X'])
        ->assertNotFound();
});
