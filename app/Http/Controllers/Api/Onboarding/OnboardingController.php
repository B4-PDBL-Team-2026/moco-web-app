<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Domains\Budgeting\Actions\CompleteOnboardingAction;
use App\Domains\Budgeting\DTOs\CompleteOnboardingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budgeting\StoreOnboardingRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class OnboardingController extends Controller
{
    use ApiResponse;

    /**
     * @throws Throwable
     */
    public function store(StoreOnboardingRequest $request, CompleteOnboardingAction $action): JsonResponse
    {
        $dto = CompleteOnboardingData::fromData($request->validated());

        $result = $action->execute(
            userId: auth()->id(),
            data: $dto
        );

        return $this->success(
            data: $result,
            message: 'Onboarding completed successfully.'
        );
    }
}
