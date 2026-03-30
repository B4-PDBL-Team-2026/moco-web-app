<?php

namespace App\Http\Middleware;

use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckDailyBudgetRecalculation
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $settings = UserBudgetSetting::where('user_id', $user->id)->first();

        if (! $settings) {
            return $next($request);
        }

        $timezone = $settings->timezone ?? 'Asia/Jakarta';

        $now = CarbonImmutable::now($timezone);

        $snapshot = UserBudgetSnapshot::where('user_id', $user->id)->first();

        $needsRecalculation = false;

        if (! $snapshot || ! $snapshot->recalculated_at) {
            $needsRecalculation = true;
        } else {
            $lastRecalculated = CarbonImmutable::parse($snapshot->recalculated_at)
                ->setTimezone($timezone);

            if (! $now->isSameDay($lastRecalculated)) {
                $needsRecalculation = true;
            }
        }

        if ($needsRecalculation) {
            app(RecalculateBudgetSnapshotAction::class)->execute($user->id, $now);
        }

        return $next($request);
    }
}
