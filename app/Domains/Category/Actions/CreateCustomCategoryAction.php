<?php

namespace App\Domains\Category\Actions;

use App\Domains\Category\DTOs\CreateCustomCategoryData;
use App\Domains\Category\Models\Category;

final readonly class CreateCustomCategoryAction
{
    /**
     * Create a new custom category for a specific user.
     * System flag is strictly forced to false to prevent users from creating system-wide categories.
     *
     * @param  int  $userId  The ID of the authenticated user creating the category.
     * @param  CreateCustomCategoryData  $data  The validated payload.
     */
    public function execute(int $userId, CreateCustomCategoryData $data): Category
    {
        return Category::create([
            'user_id' => $userId,
            'name' => $data->name,
            'icon' => $data->icon,
            'type' => $data->type->value,
            'is_system' => false,
        ]);
    }
}
