<?php

use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function deleteSetup(): array
{
    $user = User::factory()->create();
    $cat = SystemCategory::factory()->create();
    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Gym',
        'amount' => '250000.00',
        'cycle_type' => 'monthly',
        'due_day' => 5,
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
    ]);

    return [$user, $template, $cat];
}

test('unauthenticated request returns 401', function () {
    $this->deleteJson('/api/fixed-costs/1')->assertUnauthorized();
});

test('soft-deletes the template and returns 200', function () {
    [$user, $template] = deleteSetup();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/fixed-costs/{$template->id}")->assertOk();

    $this->assertSoftDeleted('fixed_cost_templates', ['id' => $template->id]);
});

test('voids pending occurrences on deletion', function () {
    [$user, $template] = deleteSetup();

    $occ = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'due_date' => '2026-03-05',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'amount' => '250000.00',
        'name' => 'Gym',
        'category_type' => SystemCategory::class,
        'category_id' => $template->category_id,
    ]);

    Sanctum::actingAs($user);
    $this->deleteJson("/api/fixed-costs/{$template->id}")->assertOk();

    expect($occ->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

test('preserves paid occurrences on deletion', function () {
    [$user, $template] = deleteSetup();

    $paid = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'due_date' => '2026-03-05',
        'status' => FixedCostOccurenceStatus::PAID->value,
        'amount' => '250000.00',
        'name' => 'Gym',
        'category_type' => SystemCategory::class,
        'category_id' => $template->category_id,
        'paid_at' => now(),
    ]);

    Sanctum::actingAs($user);
    $this->deleteJson("/api/fixed-costs/{$template->id}")->assertOk();

    expect($paid->fresh()->status)->toBe(FixedCostOccurenceStatus::PAID);
});

test('returns 404 when template belongs to another user', function () {
    [$_, $template] = deleteSetup();
    $otherUser = User::factory()->create();
    Sanctum::actingAs($otherUser);

    $this->deleteJson("/api/fixed-costs/{$template->id}")->assertNotFound();
});

test('returns 404 when template is already deleted', function () {
    [$user, $template] = deleteSetup();
    $template->delete();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/fixed-costs/{$template->id}")->assertNotFound();
});

test('returns 404 for a non-existent template id', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->deleteJson('/api/fixed-costs/99999')->assertNotFound();
});
