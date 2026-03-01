<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\DTOs\Auth\RegisterUserDTO;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction extends BaseAction
{
    /**
     * Register a new user with sensible defaults.
     *
     * Non-nullable profile fields (goal, cycle_type, cycle_start, balance, profile_url)
     * receive defaults here. The response flags the user for onboarding.
     *
     * @return array{user: User, token: string, is_new_user: bool, requires_onboarding: bool}
     */
    public function execute(RegisterUserDTO $dto): array
    {
        $user = User::query()->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ]);

        event(new Registered($user));

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'requires_onboarding' => true,
        ];
    }
}
