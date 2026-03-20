<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Domains\Budgeting\Actions\CompleteOnboardingAction;
use App\Domains\Budgeting\Actions\UpdateInitialBalanceAction;
use App\Domains\Budgeting\DTOs\CompleteOnboardingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StoreOnboardingRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class OnboardingController extends Controller
{
    use ApiResponse;

    public function show(UpdateInitialBalanceAction $action): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $result = $action->execute($user);

        return $this->success($result);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreOnboardingRequest $request, CompleteOnboardingAction $action): JsonResponse
    {
        $dto = CompleteOnboardingData::fromRequest($request);

        /** @var User $user */
        $user = auth()->user();

        $action->execute($user, $dto);

        return $this->success(
            message: 'Onboarding data successfully stored.'
        );
    }
}
