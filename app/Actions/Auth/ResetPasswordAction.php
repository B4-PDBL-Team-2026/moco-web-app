<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordAction extends BaseAction
{
    /**
     * Reset the user's password using a valid token.
     *
     * @return array{status: string, message: string}
     */
    public function execute(string $email, string $password, string $token): array
    {
        $status = Password::reset(
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
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
