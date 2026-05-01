<?php

use App\Models\User;
use App\Models\UserBudgetSetting;
use Laravel\Sanctum\Sanctum;

describe('GET /api/settings/dailyLimit', function () {
    it('requires authentication', function () {
        $this->getJson('/api/settings/dailyLimit')
            ->assertUnauthorized();
    });

    it('returns user daily limit successfully', function () {
        [$user] = setupUserWithBudget();

        Sanctum::actingAs($user);

        $this->getJson('/api/settings/dailyLimit')
            ->assertOk() // Status 200
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'flooringLimit',
                    'ceilingLimit',
                ],
            ]);
    });

    it('returns formatted error response when budget setting is missing', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/settings/dailyLimit')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.businessRule.0', __('errors.budget.budget_setting_not_found'));
    });
});

describe('PATCH /api/settings/dailyLimit', function () {
    it('requires authentication', function () {
        $this->patchJson('/api/settings/dailyLimit', [])
            ->assertUnauthorized();
    });

    it('validates required fields', function () {
        [$user] = setupUserWithBudget();
        Sanctum::actingAs($user);

        $this->patchJson('/api/settings/dailyLimit', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['flooringLimit', 'ceilingLimit']);
    });

    it('validates that flooringLimit must be numeric and min 1', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/settings/dailyLimit', [
            'flooringLimit' => 0,
            'ceilingLimit' => 50000,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['flooringLimit']);
    });

    it('validates that ceilingLimit must be greater than or equal to flooringLimit', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/settings/dailyLimit', [
            'flooringLimit' => 50000,
            'ceilingLimit' => 10000,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ceilingLimit']);
    });

    it('successfully updates user daily limit via API', function () {
        $user = User::factory()->create();
        UserBudgetSetting::factory()->create([
            'user_id' => $user->id,
            'flooring_limit' => 1000,
            'ceiling_limit' => 2000,
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/settings/dailyLimit', [
            'flooringLimit' => 15000,
            'ceilingLimit' => 35000,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'successfully update user budget limit');

        $this->assertDatabaseHas('user_budget_settings', [
            'user_id' => $user->id,
            'flooring_limit' => 15000,
            'ceiling_limit' => 35000,
        ]);
    });
});
