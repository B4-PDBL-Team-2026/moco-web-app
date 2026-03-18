<?php

namespace App\Domains\Auth\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Verified;

class VerifyEmailAction
{
    /**
     * Mark the user's email as verified.
     *
     * @return array{status: string, message: string}
     */
    public function execute(User $user): array
    {
        if ($user->hasVerifiedEmail()) {
            return [
                'status' => 'success',
                'message' => 'Email is already verified.',
            ];
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return [
            'status' => 'success',
            'message' => 'Email verified successfully.',
        ];
    }
}
