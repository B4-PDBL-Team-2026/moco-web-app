<?php

namespace App\Http\Controllers\Api\Category;

use App\Domains\Category\Actions\CreateCustomCategoryAction;
use App\Domains\Category\Actions\DeleteCustomCategoryAction;
use App\Domains\Category\Actions\GetAllCustomCategoriesAction;
use App\Domains\Category\Actions\GetAllSystemCategoriesAction;
use App\Domains\Category\Actions\UpdateCustomCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCustomCategoryRequest;
use App\Http\Requests\Category\UpdateCustomCategoryRequest;
use App\Http\Resources\Category\CategoryResource;
use App\Http\Responses\ApiResponse;

class CategoryController extends Controller
{
    /**
     * Get all system categories
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: array<CategoryResource>
     * }
     */
    public function indexSystemCategories(GetAllSystemCategoriesAction $action): ApiResponse
    {
        $result = $action->execute();

        return $this->successResponse(CategoryResource::collection($result), 'Retrieved all system category successfully.');
    }

    /**
     * Get all custom categories for the authenticated user.
     *
     * @response array{
     * success: bool,
     * message: string,
     * data: array<CategoryResource>
     * }
     */
    public function indexCustomCategories(GetAllCustomCategoriesAction $action): ApiResponse
    {
        $result = $action->execute(auth()->id());

        return $this->successResponse(
            data: CategoryResource::collection($result),
            message: 'Retrieved user custom categories successfully.'
        );
    }

    /**
     * Create a new custom category.
     *
     * @response array{
     * success: bool,
     * message: string,
     * data: CategoryResource
     * }
     */
    public function store(StoreCustomCategoryRequest $request, CreateCustomCategoryAction $action): ApiResponse
    {
        $category = $action->execute(
            userId: $request->user()->id,
            data: $request->toDTO()
        );

        return $this->successResponse(
            data: CategoryResource::make($category),
            message: 'Custom category created successfully.',
            status: 201
        );
    }

    /**
     * Update an existing custom category.
     *
     * @response array{
     * success: bool,
     * message: string,
     * data: CategoryResource
     * }
     */
    public function update(
        int $categoryId,
        UpdateCustomCategoryRequest $request,
        UpdateCustomCategoryAction $action
    ): ApiResponse {
        $category = $action->execute(
            userId: $request->user()->id,
            categoryId: $categoryId,
            data: $request->toDTO()
        );

        return $this->successResponse(
            data: CategoryResource::make($category),
            message: 'Custom category updated successfully.'
        );
    }

    /**
     * Delete a custom category.
     *
     * @response array{
     * success: bool,
     * message: string
     * }
     */
    public function destroy(int $categoryId, DeleteCustomCategoryAction $action): ApiResponse
    {
        $action->execute(
            userId: auth()->id(),
            categoryId: $categoryId
        );

        return $this->successResponse(
            message: 'Custom category deleted successfully.',
            status: 204,
        );
    }
}
