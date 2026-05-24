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
        $user = $request->user();

        if (! $user?->has_onboarded) {
            return $next($request);
        }

        // deny onboarding action
        if ($request->expectsJson() || $request->is('api/*')) {
            return (new ApiResponse(
                errors: [
                    'requiresOnboarding' => false,
                ],
                message: __('errors.validation.onboarded'),
                status: Response::HTTP_FORBIDDEN,
                success: false
            ))->toResponse($request);
        }

        return redirect()->route('dashboard');
    }
}
