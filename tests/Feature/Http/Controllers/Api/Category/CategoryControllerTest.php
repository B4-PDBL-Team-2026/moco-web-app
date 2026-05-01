<?php

use App\Domains\Category\Actions\GetSystemCategoriesAction;
use App\Domains\User\Models\User;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    GetSystemCategoriesAction::clearCache();
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
