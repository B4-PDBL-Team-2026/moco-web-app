<?php

namespace App\Domains\User\Actions\Auth;

use App\Domains\User\DTOs\Auth\ResetPasswordData;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordAction
{
    /**
     * Reset the user's password using a valid token.
     *
     * @return array{status: string, message: string}
     */
    public function execute(ResetPasswordData $data): array
    {
        $status = Password::reset(
            [
                'email' => $data->email,
                'password' => $data->password,
                'password_confirmation' => $data->password,
                'token' => $data->token,
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
