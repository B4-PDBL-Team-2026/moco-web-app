<?php

namespace App\Console\Commands;

use App\Domains\User\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReleaseBannedUsers extends Command
{
    protected $signature = 'users:release-bans';

    public function handle(): void
    {
        $released = 0;

        User::query()
            ->where('status', 'banned')
            ->whereNotNull('banned_until')
            ->each(function (User $user) use (&$released) {
                $timezone = $user->budgetSetting?->timezone ?? 'Asia/Jakarta';

                $bannedUntilInUserTz = Carbon::parse($user->banned_until)->setTimezone($timezone);
                $nowInUserTz = Carbon::now($timezone);

                if ($nowInUserTz->greaterThanOrEqualTo($bannedUntilInUserTz)) {
                    $user->update([
                        'status' => 'active',
                        'ban_duration' => null,
                        'banned_until' => null,
                    ]);

                    $released++;
                }
            });

        $this->info("Released {$released} banned user(s).");
    }
}
