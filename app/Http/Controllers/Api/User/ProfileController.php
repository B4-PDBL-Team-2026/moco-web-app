<?php

namespace App\Http\Controllers\Api\User;

use App\Domains\User\Actions\Profile\GetProfileAction;
use App\Domains\User\Actions\Profile\UpdateProfileAction;
use App\Domains\User\DTOs\Profile\UpdateProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Profile\UpdateProfileRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * Return the authenticated user's profile.
     */
    public function show(GetProfileAction $action): JsonResponse
    {
        /** @var int $userId */
        $userId = auth()->id();

        $user = $action->execute($userId);

        return $this->success($user->load('profile'), 'Profile retrieved successfully.');
    }

    /**
     * Update the authenticated user's profile.
     * Only sends fields that were actually provided (partial update).
     */
    public function update(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        /** @var int $userId */
        $userId = auth()->id();

        $dto = UpdateProfileData::fromArray($request->validated());

        $profile = $action->execute($userId, $dto);

        return $this->success($profile, 'Profile updated successfully.');
    }
}
