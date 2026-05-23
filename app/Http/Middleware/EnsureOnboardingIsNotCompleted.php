<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsNotCompleted
{
    public function handle(Request $request, Closure $next): RedirectResponse|Response
    {
        if ($request->user()?->has_onboarded) {
            // handle web
            if (! $request->expectsJson()) {
                return redirect()->route('dashboard');
            }

            // handle api
            return (new ApiResponse(
                errors: [
                    'requiresOnboarding' => false,
                ],
                message: __('errors.validation.onboarded'),
                status: Response::HTTP_FORBIDDEN,
                success: false
            ))->toResponse($request);
        }

        return $next($request);
    }
}
