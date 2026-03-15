<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsCompleted
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasOnboarded) {
            return $next($request);
        }

        return $this->error(
            'Onboarding required.',
            Response::HTTP_FORBIDDEN,
            [
                'requires_onboarding' => true,
            ]
        );
    }
}
