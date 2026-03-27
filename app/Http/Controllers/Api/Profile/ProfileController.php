<?php

namespace App\Http\Controllers\Api\Profile;

use App\Domains\Profile\Actions\GetProfileAction;
use App\Domains\Profile\Actions\UpdateProfileAction;
use App\Domains\Profile\DTOs\UpdateProfileData;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Http\Requests\Profile\UpdateProfileRequest;
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