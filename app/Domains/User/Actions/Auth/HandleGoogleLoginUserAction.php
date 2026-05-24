<?php

namespace App\Domains\User\Actions\Auth;

use App\Domains\User\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Throwable;

class HandleGoogleLoginUserAction
{
    /**
     * @throws Throwable
     */
    public function execute(ProviderUser $providerUser)
    {
        return DB::transaction(function () use ($providerUser) {
            $user = User::where('email', '=', $providerUser->getEmail())
                ->orWhere('google_id', '=', $providerUser->getId())
                ->first();

            if (! $user) {
                $user = User::create([
                    'name' => $providerUser->getName(),
                    'email' => $providerUser->getEmail(),
                    'google_id' => $providerUser->getId(),
                    'email_verified_at' => now(),
                    'has_onboarded' => false,
                ]);

                $user->profile()->create([
                    'display_name' => $providerUser->getName(),
                    'avatar_url' => $providerUser->getAvatar(),
                ]);
            } else {
                if (! $user->google_id) {
                    $user->update(['google_id' => $providerUser->getId()]);
                }
            }

            return $user;
        });
    }
}
