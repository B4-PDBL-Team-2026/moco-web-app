<?php

namespace App\Domains\User\Actions\Auth;

use App\Domains\User\DTOs\Auth\LoginUserData;
use App\Domains\User\Exceptions\UserBannedException;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    /**
     * Authenticate a user and issue a Sanctum token.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     * @throws UserBannedException
     */
    public function execute(LoginUserData $data): array
    {
        $user = User::query()->where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->isBanned()) {
            throw new UserBannedException($user->banned_until);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'requiresOnboarding' => $user->isRequireOnboarding(),
        ];
    }
}
