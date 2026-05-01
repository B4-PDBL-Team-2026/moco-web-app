<?php

use App\Models\Category;
use App\Models\FixedCostTemplate;
use App\Models\User;
use App\Models\UserBudgetSetting;
use Laravel\Sanctum\Sanctum;

function updateSetup(): array
{
    $user = User::factory()->create(['has_onboarded' => true]);

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'cycle_type' => 'monthly',
        'ceiling_limit' => '500000.00',
        'flooring_limit' => '10000.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    $cat = Category::factory()->expense()->create();
    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Netflix',
        'amount' => '150000.00',
        'cycle_type' => 'monthly',
        'due_day' => 15,
        'is_active' => true,
        'category_id' => $cat->id,
    ]);

    return [$user, $template, $cat];
}

test('unauthenticated request returns 401', function () {
    $this->patchJson('/api/fixed-costs/1', [])->assertUnauthorized();
});

test(' when name is empty string', function () {
    [$user, $template] = updateSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/{$template->id}", ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test(' when amount is zero', function () {
    [$user, $template] = updateSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/$template->id", ['amount' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test(' when cycleType is invalid value', function () {
    [$user, $template] = updateSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/{$template->id}", ['cycleType' => 'biweekly'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cycleType']);
});

test(' when dueDay is 0', function () {
    [$user, $template] = updateSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/{$template->id}", ['dueDay' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['dueDay']);
});

test('updates template name and returns 200', function () {
    [$user, $template] = updateSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/{$template->id}", ['name' => 'Spotify'])
        ->assertOk();

    expect($template->fresh()->name)->toBe('Spotify');
});

test('updates isActive to false', function () {
    [$user, $template] = updateSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/{$template->id}", ['isActive' => false])
        ->assertOk();

    expect($template->fresh()->is_active)->toBeFalse();
});

test('updates amount when no paid occurrences exist', function () {
    [$user, $template] = updateSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/{$template->id}", ['amount' => 200000])
        ->assertOk();

    expect((string) $template->fresh()->amount)->toBe('200000.00');
});

test('sparse update — only provided fields change', function () {
    [$user, $template] = updateSetup();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/{$template->id}", ['name' => 'Disney+'])
        ->assertOk();

    $fresh = $template->fresh();
    expect($fresh->name)->toBe('Disney+')
        ->and((string) $fresh->amount)->toBe('150000.00')
        ->and($fresh->due_day)->toBe(15);
});

test('returns 404 when template belongs to another user', function () {
    [$_, $template] = updateSetup();
    $otherUser = User::factory()->create();
    UserBudgetSetting::factory()->create(['user_id' => $otherUser->id, 'cycle_type' => 'monthly']);
    Sanctum::actingAs($otherUser);

    $this->patchJson("/api/fixed-costs/{$template->id}", ['name' => 'Hack'])
        ->assertNotFound();
});

test('returns 404 for a soft-deleted template', function () {
    [$user, $template] = updateSetup();
    $template->delete();
    Sanctum::actingAs($user);

    $this->patchJson("/api/fixed-costs/{$template->id}", ['name' => 'X'])
        ->assertNotFound();
});
