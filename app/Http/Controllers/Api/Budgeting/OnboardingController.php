<?php

namespace App\Http\Controllers\Api\Budgeting;

use App\Domains\Budgeting\Actions\CompleteOnboardingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budgeting\StoreOnboardingRequest;
use App\Http\Resources\Budgeting\OnboardingResultResource;
use App\Http\Responses\ApiResponse;
use Throwable;

class OnboardingController extends Controller
{
    /**
     * Complete user onboarding
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: OnboardingResultResource
     * }
     *
     * @throws Throwable
     */
    public function store(StoreOnboardingRequest $request, CompleteOnboardingAction $action): ApiResponse
    {
        $result = $action->execute(
            userId: auth()->id(),
            data: $request->toDTO(),
        );

        return $this->successResponse(
            data: OnboardingResultResource::make($result),
            message: 'Onboarding completed successfully.'
        );
    }
}
