<?php

namespace App\Domains\User\Actions\Auth;

use App\Domains\User\Models\User;
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
