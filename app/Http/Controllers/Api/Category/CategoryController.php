<?php

namespace App\Http\Controllers\Api\Category;

use App\Domains\Category\Actions\GetSystemCategoriesAction;
use App\Http\Controllers\Controller;
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
    public function getAllSystemCategory(GetSystemCategoriesAction $action): ApiResponse
    {
        $result = $action->execute();

        return $this->successResponse(CategoryResource::collection($result), 'Retrieved all system category successfully.');
    }
}
