<?php

namespace App\Http\Controllers\Api\User;

use App\Domains\User\Actions\Profile\GetProfileAction;
use App\Domains\User\Actions\Profile\UpdateProfileAction;
use App\Domains\User\DTOs\Profile\UpdateProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Profile\UpdateProfileRequest;
use App\Http\Responses\ApiResponse;

class ProfileController extends Controller
{
    /**
     * Return the authenticated user's profile.
     */
    public function show(GetProfileAction $action): ApiResponse
    {
        $userId = auth()->id();

        $user = $action->execute($userId);

        return $this->successResponse($user->load('profile'), 'Profile retrieved successfully.');
    }

    /**
     * Update the authenticated user's profile.
     * Only sends fields that were actually provided (partial update).
     */
    public function update(UpdateProfileRequest $request, UpdateProfileAction $action): ApiResponse
    {
        $userId = auth()->id();

        $dto = UpdateProfileData::fromArray($request->validated());

        $profile = $action->execute($userId, $dto);

        return $this->successResponse(
            data: $profile,
            message: 'Profile updated successfully.',
        );
    }
}
