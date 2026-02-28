<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class VerifyEmailAction extends BaseAction
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
                'status' => 'already_verified',
                'message' => 'Email sudah diverifikasi.',
            ];
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return [
            'status' => 'success',
            'message' => 'Email berhasil diverifikasi.',
        ];
    }
}
