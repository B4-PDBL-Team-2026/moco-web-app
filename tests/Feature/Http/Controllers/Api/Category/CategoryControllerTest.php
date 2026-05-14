<?php

use App\Domains\Category\Actions\GetAllSystemCategoriesAction;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    GetAllSystemCategoriesAction::clearCache();
});

test('unauthenticated user cannot fetch system categories', function () {
    $this->getJson('/api/category/system')
        ->assertUnauthorized();
});

test('authenticated user can fetch system categories with correct structure', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/category/system')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'type',
                    'icon',
                ],
            ],
        ])
        ->assertJsonCount(17, 'data');
});

test('it returns categories in alphabetical order via API', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/category/system');

    expect($response->json('data.0.name'))->toBe('Belanja');
});

it('denies access to unauthenticated users for all custom category endpoints', function () {
    $this->getJson('/api/category/custom')->assertUnauthorized();
    $this->postJson('/api/category/custom', [])->assertUnauthorized();
    $this->patchJson('/api/category/custom/1', [])->assertUnauthorized();
    $this->deleteJson('/api/category/custom/1')->assertUnauthorized();
});

// GET: RETRIEVE CUSTOM CATEGORIES

it('retrieves all custom categories for the authenticated user', function () {
    $user = User::factory()->create();

    Category::factory()->count(2)->create([
        'user_id' => $user->id,
        'is_system' => false,
    ]);

    Category::factory()->create([
        'user_id' => null,
        'is_system' => true,
    ]);

    $response = $this->actingAs($user)->getJson('/api/category/custom');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'name', 'icon', 'type', 'isSystem', 'userId'],
            ],
        ])
        ->assertJsonCount(2, 'data');
});

// POST: CREATE CUSTOM CATEGORY

it('successfully creates a new custom category', function () {
    $user = User::factory()->create();
    $payload = [
        'name' => 'Bonus Tahunan',
        'icon' => 'ic_gift',
        'type' => TransactionType::INCOME->value,
    ];

    $response = $this->actingAs($user)->postJson('/api/category/custom', $payload);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Custom category created successfully.',
        ])
        ->assertJsonPath('data.name', 'Bonus Tahunan')
        ->assertJsonPath('data.isSystem', false);

    $this->assertDatabaseHas('categories', [
        'user_id' => $user->id,
        'name' => 'Bonus Tahunan',
        'is_system' => false,
    ]);
});

it('fails to create a category with validation errors', function () {
    $user = User::factory()->create();

    $payload = [
        'name' => '',
        'icon' => 'ic_gift',
        'type' => 'INVALID_TYPE',
    ];

    $response = $this->actingAs($user)->postJson('/api/category/custom', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'type']);
});

// PATCH: UPDATE CUSTOM CATEGORY

it('successfully updates an existing custom category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'is_system' => false,
        'name' => 'Gaji Bulanan',
    ]);

    $payload = [
        'name' => 'Gaji Freelance',
    ];

    $response = $this->actingAs($user)->patchJson("/api/category/custom/{$category->id}", $payload);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Gaji Freelance');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Gaji Freelance',
    ]);
});

it('prevents a user from updating a system category', function () {
    $user = User::factory()->create();
    $systemCategory = Category::factory()->create(['is_system' => true]);

    $response = $this->actingAs($user)
        ->patchJson("/api/category/custom/{$systemCategory->id}", ['name' => 'Hacked Name']);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);

    $this->assertDatabaseMissing('categories', ['name' => 'Hacked Name']);
});

it('prevents a user from updating another users category', function () {
    $hacker = User::factory()->create();
    $victim = User::factory()->create();
    $victimCategory = Category::factory()->create([
        'user_id' => $victim->id,
        'is_system' => false,
    ]);

    $response = $this->actingAs($hacker)
        ->patchJson("/api/category/custom/{$victimCategory->id}", ['name' => 'Hacked Name']);

    $response->assertStatus(422);
});

// DELETE: DESTROY CUSTOM CATEGORY

it('successfully deletes a custom category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'is_system' => false,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/category/custom/{$category->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

it('returns 404 when trying to delete a non-existent category', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->deleteJson('/api/category/custom/999999');

    $response->assertNotFound();
});
