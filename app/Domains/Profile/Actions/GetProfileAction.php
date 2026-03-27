<?php

namespace App\Domains\Profile\Actions;

use App\Models\User;

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