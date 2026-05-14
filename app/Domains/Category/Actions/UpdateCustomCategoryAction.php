<?php

namespace App\Domains\Category\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Category\DTOs\UpdateCustomCategoryData;
use App\Domains\Category\Models\Category;

final readonly class UpdateCustomCategoryAction
{
    /**
     * Update an existing custom category.
     *
     * * @param int $userId The ID of the authenticated user requesting the update.
     * @param  int  $categoryId  The ID of the category to be updated.
     * @param  UpdateCustomCategoryData  $data  The validated payload containing optional update fields.
     *
     * * @throws BusinessRuleException If the category is a system category or doesn't belong to the user.
     */
    public function execute(int $userId, int $categoryId, UpdateCustomCategoryData $data): Category
    {
        $category = Category::findOrFail($categoryId);

        $this->ensureUserCanModifyCategory($userId, $category);

        $category->update(array_filter([
            'name' => $data->name,
            'icon' => $data->icon,
            'type' => $data->type?->value,
        ], fn ($value) => $value !== null));

        return $category->refresh();
    }

    /**
     * Validate ownership and system flag to prevent unauthorized modifications.
     *
     * * @throws BusinessRuleException
     */
    private function ensureUserCanModifyCategory(int $userId, Category $category): void
    {
        if ($category->is_system) {
            throw new BusinessRuleException(__('errors.category.cannot_modify_system'));
        }

        if ($category->user_id !== $userId) {
            throw new BusinessRuleException(__('errors.authorization.not_authorized'));
        }
    }
}
