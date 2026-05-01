<?php

namespace App\Http\Controllers\Api\Budgeting;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\GetDailyLimitAction;
use App\Domains\Budgeting\Actions\UpdateDailyLimitAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budgeting\UpdateDailyLimitRequest;
use App\Http\Resources\Budgeting\DailyLimitResource;
use App\Http\Responses\ApiResponse;
use Throwable;

class BudgetingController extends Controller
{
    /**
     * Get user daily limit
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: DailyLimitResource
     * }
     *
     * @throws BusinessRuleException
     */
    public function getUserDailyLimit(GetDailyLimitAction $action): ApiResponse
    {
        $result = $action->execute(auth()->user());

        return $this->successResponse(
            data: $result->toResource(DailyLimitResource::class),
            message: 'successfully retrieve user budget limit'
        );
    }

    /**
     * Update user daily limit
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: DailyLimitResource
     * }
     *
     * @throws BusinessRuleException|Throwable
     */
    public function updateUserDailyLimit(UpdateDailyLimitRequest $request, UpdateDailyLimitAction $action): ApiResponse
    {
        $result = $action->execute(auth()->user(), $request->toDTO());

        return $this->successResponse(
            data: $result->toResource(DailyLimitResource::class),
            message: 'successfully update user budget limit'
        );
    }
}
