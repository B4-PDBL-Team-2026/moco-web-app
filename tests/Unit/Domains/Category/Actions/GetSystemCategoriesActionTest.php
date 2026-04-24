<?php

use App\Domains\Category\Actions\GetSystemCategoriesAction;
use App\Models\Category;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    GetSystemCategoriesAction::clearCache();
});

it('retrieves all system categories sorted by name', function () {
    $action = app(GetSystemCategoriesAction::class);
    $categories = $action->execute();

    expect($categories)->toHaveCount(17)
        ->and($categories->first()->name)->toBe('Belanja')
        ->and($categories->last()->name)->toBe('Uang saku');
});

it('caches the result after the first call', function () {
    $action = app(GetSystemCategoriesAction::class);

    $action->execute();

    Category::factory()->create(['name' => 'Kategori Baru']);

    $categories = $action->execute();
    expect($categories)->toHaveCount(17);
});

it('returns updated data after cache is cleared', function () {
    $action = app(GetSystemCategoriesAction::class);

    $action->execute();

    Category::factory()->create(['name' => 'Zzz Kategori']);

    GetSystemCategoriesAction::clearCache();

    $categories = $action->execute();
    expect($categories)->toHaveCount(18);
});
