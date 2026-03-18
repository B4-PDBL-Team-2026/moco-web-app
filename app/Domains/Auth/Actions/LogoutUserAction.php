<?php

namespace App\Domains\Auth\Actions;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutUserAction
{
    public function execute(User $user): void
    {
        $currentAccessToken = $user->currentAccessToken();

        if (! $currentAccessToken instanceof PersonalAccessToken) {
            return;
        }

        $currentAccessToken->delete();
    }
}
