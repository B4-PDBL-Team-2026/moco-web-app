<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\LoginUserDTO;
use App\Models\User;
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
     */
    public function execute(LoginUserDTO $dto): array
    {
        $user = User::query()->where('email', $dto->email)->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email or password is wrong.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'requires_onboarding' => $user->isRequireOnboarding(),
        ];
    }
}
