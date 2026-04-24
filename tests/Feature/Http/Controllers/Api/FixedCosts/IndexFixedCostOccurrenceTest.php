<?php

use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\Category;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\User;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

function indexSetup(): array
{
    $user = User::factory()->create();
    $cat = Category::factory()->expense()->create();

    $snapshot = UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 10,
    ]);

    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'category_id' => $cat->id,
    ]);

    return [$user, $template, $cat, $snapshot];
}

test('unauthenticated request returns 401', function () {
    $this->getJson('/api/fixed-costs/occurrences')->assertUnauthorized();
});

test('returns 200 with an empty array when no occurrences exist', function () {
    [$user] = indexSetup();
    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs/occurrences')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('returns monthly occurrences within the current budget window', function () {
    [$user, $template] = indexSetup();

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'due_date' => '2026-03-15',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'amount' => '150000.00',
        'name' => 'Netflix',
        'category_id' => $template->category_id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs/occurrences')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('returns weekly occurrences within a monthly budget window', function () {
    [$user, $template] = indexSetup();

    foreach (['2026-03-09', '2026-03-16'] as $index => $date) {
        FixedCostOccurrence::factory()->create([
            'user_id' => $user->id,
            'fixed_cost_template_id' => $template->id,
            'cycle_key' => '2026-W'.(10 + $index),
            'cycle_type' => 'weekly',
            'due_date' => $date,
            'status' => FixedCostOccurenceStatus::PENDING->value,
            'amount' => '50000.00',
            'name' => 'Weekly Sub',
            'category_id' => $template->category_id,
        ]);
    }

    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs/occurrences')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('excludes occurrences outside the current window', function () {
    [$user, $template] = indexSetup();

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-02',
        'cycle_type' => 'monthly',
        'due_date' => '2026-02-15',
        'status' => FixedCostOccurenceStatus::PAID->value,
        'amount' => '150000.00',
        'name' => 'Netflix',
        'category_id' => $template->category_id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs/occurrences')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('does not return occurrences belonging to another user', function () {
    [$user] = indexSetup();

    $other = User::factory()->create();
    $otherSnap = UserBudgetSnapshot::factory()->create([
        'user_id' => $other->id,
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 10,
    ]);
    $otherCat = Category::factory()->expense()->create();
    $otherTpl = FixedCostTemplate::factory()->create(['user_id' => $other->id, 'category_id' => $otherCat->id]);
    FixedCostOccurrence::factory()->create([
        'user_id' => $other->id,
        'fixed_cost_template_id' => $otherTpl->id,
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'due_date' => '2026-03-15',
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'amount' => '99999.00',
        'name' => 'Other',
        'category_id' => $otherCat->id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs/occurrences')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('returns 404 when user has no budget snapshot (not onboarded)', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs/occurrences')->assertNotFound();
});
