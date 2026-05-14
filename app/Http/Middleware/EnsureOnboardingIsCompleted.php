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

        if ($user?->hasOnboarded) {
            return $next($request);
        }

        return (new ApiResponse(
            errors: [
                'requiresOnboarding' => true,
            ],
            message: __('errors.validation.onboarding'),
            status: Response::HTTP_FORBIDDEN,
            success: false
        ))->toResponse($request);
    }
}
