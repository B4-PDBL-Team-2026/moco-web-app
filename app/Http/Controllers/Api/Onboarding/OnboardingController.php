<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Actions\Onboarding\ProcessOnboardingAction;
use App\Actions\Onboarding\RetrieveOnboardingDataAction;
use App\DTOs\Onboarding\StoreOnboardingUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOnboardingRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class OnboardingController extends Controller
{
    use ApiResponse;

    public function show(RetrieveOnboardingDataAction $action): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $result = $action->execute($user);

        return $this->success($result);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreOnboardingRequest $request, ProcessOnboardingAction $action): JsonResponse
    {
        $dto = StoreOnboardingUserDTO::fromRequest($request);

        /** @var User $user */
        $user = auth()->user();

        $action->execute($user, $dto);

        return $this->success(
            message: 'Onboarding data successfully stored.'
        );
    }
}
