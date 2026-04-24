<?php

namespace App\Http\Controllers\Api\Budgeting;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\GetDailyLimitAction;
use App\Domains\Budgeting\Actions\UpdateDailyLimitAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budgeting\UpdateDailyLimitRequest;
use App\Http\Resources\Budgeting\DailyLimitResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class BudgetingController extends Controller
{
    use ApiResponse;

    /**
     * @throws BusinessRuleException
     */
    public function getUserDailyLimit(GetDailyLimitAction $action): JsonResponse
    {
        $result = $action->execute(auth()->user());

        return $this->success(
            $result->toResource(DailyLimitResource::class),
            'successfully retrieve user budget limit'
        );
    }

    /**
     * @throws BusinessRuleException
     */
    public function updateUserDailyLimit(UpdateDailyLimitRequest $request, UpdateDailyLimitAction $action): JsonResponse
    {
        $result = $action->execute(auth()->user(), $request->toDTO());

        return $this->success(
            $result->toResource(DailyLimitResource::class),
            'successfully update user budget limit'
        );
    }
}
