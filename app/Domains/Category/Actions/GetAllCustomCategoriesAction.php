<?php

namespace App\Domains\Category\Actions;

use App\Domains\Category\Models\Category;
use Illuminate\Support\Collection;

final readonly class GetAllCustomCategoriesAction
{
    /**
     * Retrieve all custom categories belonging to a specific user.
     * Does not include system-default categories.
     *
     * @param  int  $userId  The ID of the user.
     * @return Collection<int, Category>
     */
    public function execute(int $userId): Collection
    {
        return Category::query()
            ->where('user_id', $userId)
            ->where('is_system', false)
            ->orderBy('name')
            ->get();
    }
}
