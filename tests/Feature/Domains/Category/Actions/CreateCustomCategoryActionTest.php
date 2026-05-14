<?php

use App\Domains\Category\Actions\CreateCustomCategoryAction;
use App\Domains\Category\DTOs\CreateCustomCategoryData;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;

it('successfully creates a custom category for the user', function () {
    $user = User::factory()->create();
    $data = new CreateCustomCategoryData(
        name: 'Gaji Freelance',
        icon: 'ic_money',
        type: TransactionType::INCOME,
    );

    $action = app(CreateCustomCategoryAction::class);
    $category = $action->execute($user->id, $data);

    expect($category->name)->toBe('Gaji Freelance')
        ->and($category->user_id)->toBe($user->id)
        ->and($category->is_system)->toBeFalse();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'user_id' => $user->id,
        'name' => 'Gaji Freelance',
        'is_system' => false,
    ]);
});
