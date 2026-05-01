<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\RegisterUserData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    /**
     * Register a new user with sensible defaults.
     *
     * Non-nullable profile fields (goal, cycle_type, cycle_start, balance, profile_url)
     * receive defaults here. The response flags the user for onboarding.
     *
     * @return array{user: User, token: string, is_new_user: bool, requires_onboarding: bool}
     */
    public function execute(RegisterUserData $data): array
    {
        $user = User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'requiresOnboarding' => true,
        ];
    }
}
