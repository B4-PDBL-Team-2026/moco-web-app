<?php

namespace App\Domains\User\Actions\Profile;

use App\Domains\User\DTOs\Profile\UpdateProfileData;
use App\Domains\User\Models\User;
use App\Domains\User\Models\UserProfile;

class UpdateProfileAction
{
    /**
     * Update or create the user's profile with the provided data.
     * Only non-null fields in the DTO will overwrite existing values.
     */
    public function execute(int $userId, UpdateProfileData $data): UserProfile
    {
        $user = User::findOrFail($userId);

        $profile = $user->profile()->firstOrNew(['user_id' => $userId]);

        if ($data->displayName !== null) {
            $profile->display_name = $data->displayName;
        }

        if ($data->avatarUrl !== null) {
            $profile->avatar_url = $data->avatarUrl;
        }

        if ($data->currency !== null) {
            $profile->currency = $data->currency;
        }

        if ($data->locale !== null) {
            $profile->locale = $data->locale;
        }

        $profile->save();

        return $profile->fresh();
    }
}
