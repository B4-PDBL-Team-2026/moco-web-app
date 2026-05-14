<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Category\Actions\DeleteCustomCategoryAction;
use App\Domains\Category\Models\Category;
use App\Domains\User\Models\User;

it('successfully deletes a custom category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'is_system' => false,
    ]);

    $action = app(DeleteCustomCategoryAction::class);
    $result = $action->execute($user->id, $category->id);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('throws BusinessRuleException when trying to delete a system category', function () {
    $user = User::factory()->create();
    $systemCategory = Category::factory()->create(['is_system' => true]);

    $action = app(DeleteCustomCategoryAction::class);

    expect(fn () => $action->execute($user->id, $systemCategory->id))
        ->toThrow(BusinessRuleException::class, __('errors.category.cannot_modify_system'));

    $this->assertDatabaseHas('categories', ['id' => $systemCategory->id]);
});

it('throws BusinessRuleException when trying to delete another users category', function () {
    $hacker = User::factory()->create();
    $victim = User::factory()->create();

    $victimCategory = Category::factory()->create([
        'user_id' => $victim->id,
        'is_system' => false,
    ]);

    $action = app(DeleteCustomCategoryAction::class);

    expect(fn () => $action->execute($hacker->id, $victimCategory->id))
        ->toThrow(BusinessRuleException::class, __('errors.authorization.not_authorized'));

    $this->assertDatabaseHas('categories', ['id' => $victimCategory->id]);
});
