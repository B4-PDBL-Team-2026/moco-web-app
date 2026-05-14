<?php

use App\Domains\Category\Actions\GetAllCustomCategoriesAction;
use App\Domains\Category\Models\Category;
use App\Domains\User\Models\User;
use Illuminate\Support\Collection;

it('retrieves only custom categories belonging to the specific user', function () {
    $targetUser = User::factory()->create();
    $otherUser = User::factory()->create();

    Category::factory()->count(2)->create([
        'user_id' => $targetUser->id,
        'is_system' => false,
    ]);

    Category::factory()->create([
        'user_id' => null,
        'is_system' => true,
    ]);

    Category::factory()->create([
        'user_id' => $otherUser->id,
        'is_system' => false,
    ]);

    $action = app(GetAllCustomCategoriesAction::class);
    $result = $action->execute($targetUser->id);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->pluck('user_id')->unique()->toArray())->toBe([$targetUser->id]);
});

it('returns an empty collection if the user has no custom categories', function () {
    $user = User::factory()->create();

    Category::factory()->create(['is_system' => true]);

    $action = app(GetAllCustomCategoriesAction::class);
    $result = $action->execute($user->id);

    expect($result)->toBeEmpty();
});
