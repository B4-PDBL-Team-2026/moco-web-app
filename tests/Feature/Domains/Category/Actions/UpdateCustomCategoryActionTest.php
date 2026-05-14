<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Category\Actions\UpdateCustomCategoryAction;
use App\Domains\Category\DTOs\UpdateCustomCategoryData;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;

it('successfully updates a custom category partially', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'is_system' => false,
        'name' => 'Nama Lama',
        'icon' => 'ic_lama',
        'type' => TransactionType::EXPENSE->value,
    ]);

    $data = new UpdateCustomCategoryData(name: 'Nama Baru');

    $action = app(UpdateCustomCategoryAction::class);
    $updatedCategory = $action->execute($user->id, $category->id, $data);

    expect($updatedCategory->name)->toBe('Nama Baru')
        ->and($updatedCategory->icon)->toBe('ic_lama')
        ->and($updatedCategory->type->value)->toBe(TransactionType::EXPENSE->value);
});

it('throws BusinessRuleException when trying to update a system category', function () {
    $user = User::factory()->create();
    $systemCategory = Category::factory()->create(['is_system' => true]);
    $data = new UpdateCustomCategoryData(name: 'Hacked System Name');

    $action = app(UpdateCustomCategoryAction::class);

    expect(fn () => $action->execute($user->id, $systemCategory->id, $data))
        ->toThrow(BusinessRuleException::class, __('errors.category.cannot_modify_system'));
});

it('throws BusinessRuleException when trying to update another users category', function () {
    $hacker = User::factory()->create();
    $victim = User::factory()->create();

    $victimCategory = Category::factory()->create([
        'user_id' => $victim->id,
        'is_system' => false,
    ]);

    $data = new UpdateCustomCategoryData(name: 'Stolen Category');
    $action = app(UpdateCustomCategoryAction::class);

    expect(fn () => $action->execute($hacker->id, $victimCategory->id, $data))
        ->toThrow(BusinessRuleException::class, __('errors.authorization.not_authorized'));
});
