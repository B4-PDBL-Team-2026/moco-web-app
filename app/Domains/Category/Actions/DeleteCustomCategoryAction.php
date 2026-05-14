<?php

namespace App\Domains\Category\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Category\Models\Category;

final readonly class DeleteCustomCategoryAction
{
    /**
     * Delete a custom category belonging to the user.
     *
     * * @param int $userId The ID of the authenticated user requesting the deletion.
     * @param  int  $categoryId  The ID of the category to be deleted.
     * @return bool True if the deletion was successful.
     *
     * * @throws BusinessRuleException If the category is a system category or doesn't belong to the user.
     */
    public function execute(int $userId, int $categoryId): bool
    {
        $category = Category::findOrFail($categoryId);

        if ($category->is_system) {
            throw new BusinessRuleException(__('errors.category.cannot_modify_system'));
        }

        if ($category->user_id !== $userId) {
            throw new BusinessRuleException(__('errors.authorization.not_authorized'));
        }

        return (bool) $category->delete();
    }
}
