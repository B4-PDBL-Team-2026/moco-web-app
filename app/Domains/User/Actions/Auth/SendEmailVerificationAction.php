<?php

namespace App\Domains\User\Actions\Auth;

use App\Domains\User\Models\User;

/**
 * Sends (or re-sends) the email verification notification to the authenticated user.
 *
 * Separated from registration so the client controls when the email is dispatched,
 * rather than it firing automatically on every register call.
 */
class SendEmailVerificationAction
{
    public function execute(User $user): array
    {
        if ($user->hasVerifiedEmail()) {
            return [
                'status' => 'success',
                'message' => 'Email is already verified.',
            ];
        }

        $user->sendEmailVerificationNotification();

        return [
            'status' => 'success',
            'message' => 'Email verification link sent on email.',
        ];
    }
}
