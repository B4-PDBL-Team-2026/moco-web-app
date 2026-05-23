<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // handle web
        if (! $request->expectsJson()) {
            if ($user?->has_onboarded) {
                return redirect()->route('dashboard');
            }

            return redirect()->route('onboarding-show');
        }

        // handle api
        if (! $user?->has_onboarded) {
            return (new ApiResponse(
                errors: [
                    'requiresOnboarding' => true,
                ],
                message: __('errors.validation.not_onboarded'),
                status: Response::HTTP_FORBIDDEN,
                success: false
            ))->toResponse($request);
        }

        return $next($request);
    }
}
