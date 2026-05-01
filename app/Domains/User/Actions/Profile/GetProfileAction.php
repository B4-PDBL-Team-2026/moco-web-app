<?php

namespace App\Domains\User\Actions\Profile;

use App\Domains\User\Models\User;

class GetProfileAction
{
    /**
     * Retrieve the authenticated user's profile data.
     */
    public function execute(int $userId): User
    {
        return User::with('profile')->findOrFail($userId);
    }
}
