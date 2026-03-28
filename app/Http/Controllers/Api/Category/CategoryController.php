<?php

namespace App\Http\Controllers\Api\Category;

use App\Domains\Category\Actions\GetSystemCategoriesAction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController
{
    use ApiResponse;

    public function getAllSystemCategory(GetSystemCategoriesAction $action): JsonResponse
    {
        $result = $action->execute();

        return $this->success($result, 'Retrieved all system category successfully.');
    }
}
