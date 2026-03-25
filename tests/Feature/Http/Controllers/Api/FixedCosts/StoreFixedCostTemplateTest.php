<?php

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\FixedCosts\Actions\GenerateOccurencesForBudgetWindowAction;
use App\Models\SystemCategory;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Prevent the full budget calculation stack from running in HTTP layer tests
    $mockRecalculate = Mockery::mock(RecalculateBudgetSnapshotAction::class);
    $mockRecalculate->shouldReceive('execute')->andReturn(new UserBudgetSnapshot);
    app()->instance(RecalculateBudgetSnapshotAction::class, $mockRecalculate);

    $mockGenerate = Mockery::mock(GenerateOccurencesForBudgetWindowAction::class);
    $mockGenerate->shouldReceive('execute')->andReturn(null);
    app()->instance(GenerateOccurencesForBudgetWindowAction::class, $mockGenerate);
});

function storeSetup(): array
{
    $user = User::factory()->create(['has_onboarded' => true]);

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'cycle_type' => 'monthly',
        'ceiling_limit' => '500000.00',
        'flooring_limit' => '10000.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000000.00',
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 10,
    ]);

    $cat = SystemCategory::factory()->create();

    return [$user, $cat];
}

test('unauthenticated request returns 401', function () {
    $this->postJson('/api/fixed-costs', [])->assertUnauthorized();
});

test('returns 422 when name is missing', function () {
    [$user, $cat] = storeSetup();
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs', [
        'amount' => 150000,
        'cycleType' => 'monthly',
        'dueDay' => 15,
        'categoryType' => SystemCategory::class,
        'categoryId' => $cat->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['name'], 'data');
});

test('returns 422 when amount is zero', function () {
    [$user, $cat] = storeSetup();
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs', [
        'name' => 'Netflix',
        'amount' => 0,
        'cycleType' => 'monthly',
        'dueDay' => 15,
        'categoryType' => SystemCategory::class,
        'categoryId' => $cat->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['amount'], 'data');
});

test('returns 422 when amount is negative', function () {
    [$user, $cat] = storeSetup();
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs', [
        'name' => 'Netflix',
        'amount' => -100,
        'cycleType' => 'monthly',
        'dueDay' => 15,
        'categoryType' => SystemCategory::class,
        'categoryId' => $cat->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['amount'], 'data');
});

test('returns 422 when cycleType is invalid', function () {
    [$user, $cat] = storeSetup();
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs', [
        'name' => 'Netflix',
        'amount' => 150000,
        'cycleType' => 'quarterly',
        'dueDay' => 15,
        'categoryType' => SystemCategory::class,
        'categoryId' => $cat->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['cycleType'], 'data');
});

test('returns 422 when dueDay exceeds 31', function () {
    [$user, $cat] = storeSetup();
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs', [
        'name' => 'Netflix',
        'amount' => 150000,
        'cycleType' => 'monthly',
        'dueDay' => 32,
        'categoryType' => SystemCategory::class,
        'categoryId' => $cat->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['dueDay'], 'data');
});

test('returns 422 when categoryType is missing', function () {
    [$user, $cat] = storeSetup();
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs', [
        'name' => 'Netflix',
        'amount' => 150000,
        'cycleType' => 'monthly',
        'dueDay' => 15,
        'categoryId' => $cat->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['categoryType'], 'data');
});

test('returns 422 when categoryId is missing', function () {
    [$user, $cat] = storeSetup();
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs', [
        'name' => 'Netflix',
        'amount' => 150000,
        'cycleType' => 'monthly',
        'dueDay' => 15,
        'categoryType' => SystemCategory::class,
    ])->assertUnprocessable()->assertJsonValidationErrors(['categoryId'], 'data');
});

test('creates a template and returns 201', function () {
    [$user, $cat] = storeSetup();
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs', [
        'name' => 'Netflix',
        'amount' => 150000,
        'cycleType' => 'monthly',
        'dueDay' => 15,
        'isActive' => true,
        'categoryType' => SystemCategory::class,
        'categoryId' => $cat->id,
    ])->assertCreated();

    $this->assertDatabaseHas('fixed_cost_templates', [
        'user_id' => $user->id,
        'name' => 'Netflix',
        'cycle_type' => 'monthly',
        'due_day' => 15,
    ]);
});

test('isActive defaults to true when not provided', function () {
    [$user, $cat] = storeSetup();
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs', [
        'name' => 'Netflix',
        'amount' => 150000,
        'cycleType' => 'monthly',
        'dueDay' => 15,
        'categoryType' => SystemCategory::class,
        'categoryId' => $cat->id,
    ])->assertCreated();

    $this->assertDatabaseHas('fixed_cost_templates', [
        'user_id' => $user->id,
        'is_active' => true,
    ]);
});

test('returns 422 or 500 when monthly fixed cost is added to weekly budget', function () {
    $user = User::factory()->create(['has_onboarded' => true]);
    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'cycle_type' => 'weekly', // weekly budget
        'ceiling_limit' => '500000.00',
        'flooring_limit' => '10000.00',
        'timezone' => 'Asia/Jakarta',
    ]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_cycle_key' => '2026-W12',
        'cycle_start_date' => '2026-03-16',
        'cycle_end_date' => '2026-03-22',
        'remaining_days' => 3,
    ]);
    $cat = SystemCategory::factory()->create();
    Sanctum::actingAs($user);

    // Monthly fixed cost on weekly budget → InvalidArgumentException from action
    $response = $this->postJson('/api/fixed-costs', [
        'name' => 'Rent',
        'amount' => 1500000,
        'cycleType' => 'monthly',
        'dueDay' => 1,
        'categoryType' => SystemCategory::class,
        'categoryId' => $cat->id,
    ]);

    // Should fail (either validation or server error from action)
    $response->assertStatus(422);
    $this->assertDatabaseMissing('fixed_cost_templates', ['user_id' => $user->id]);
});

test('returns error when invalid system category id is provided', function () {
    [$user] = storeSetup();
    Sanctum::actingAs($user);

    $this->postJson('/api/fixed-costs', [
        'name' => 'Netflix',
        'amount' => 150000,
        'cycleType' => 'monthly',
        'dueDay' => 15,
        'categoryType' => SystemCategory::class,
        'categoryId' => 99999,
    ])->assertStatus(422);
});
