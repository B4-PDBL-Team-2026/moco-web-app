<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use Illuminate\Support\Facades\Password;

class ForgotPasswordAction extends BaseAction
{
    /**
     * Send a password reset link to the given email.
     *
     * @return array{status: string}
     */
    public function execute(string $email): array
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return [
                'status' => 'error',
                'message' => __($status),
            ];
        }

        return [
            'status' => 'success',
            'message' => __($status),
        ];
    }
}
