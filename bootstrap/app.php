<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Console\Commands\SendFixedCostReminders;
use App\Http\Middleware\CheckDailyBudgetRecalculation;
use App\Http\Middleware\EnsureNotAuthenticated;
use App\Http\Middleware\EnsureOnboardingIsCompleted;
use App\Http\Middleware\EnsureOnboardingIsNotCompleted;
use App\Http\Middleware\EnsureValidAdminAccess;
use App\Http\Middleware\EnsureValidUserAccess;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->alias([
            'hasOnboarded' => EnsureOnboardingIsCompleted::class,
            'notOnboarded' => EnsureOnboardingIsNotCompleted::class,
            'hasRecaculatedToday' => CheckDailyBudgetRecalculation::class,
            'notAuthenticated' => EnsureNotAuthenticated::class,
            'isAdmin' => EnsureValidAdminAccess::class,
            'isUser' => EnsureValidUserAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (Throwable $throwable, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $throwable instanceof AuthenticationException => ApiResponse::error(
                    message: __('errors.authorization.not_authenticated'),
                    status: 401,
                ),
                $throwable instanceof ValidationException => ApiResponse::error(
                    errors: $throwable->errors(),
                    message: 'Validation failed.',
                    status: 422,
                ),
                $throwable instanceof BusinessRuleException => ApiResponse::error(
                    errors: ['businessRule' => [__($throwable->getTranslationKey(), $throwable->getTranslationParams())]],
                    message: 'Business rule violation.',
                    status: $throwable->getHttpStatus()
                ),
                $throwable instanceof NotFoundHttpException => ApiResponse::error(
                    message: __('errors.notfound'),
                    status: 404
                ),
                $throwable instanceof HttpException => ApiResponse::error(
                    message: $throwable->getMessage() ?: __('errors.server_error'),
                    status: $throwable->getStatusCode()
                ),
                default => ApiResponse::error(
                    message: app()->hasDebugModeEnabled() ? $throwable->getMessage() : __('errors.server_error'),
                ),
            };
        });
    })
    ->withCommands([
        SendFixedCostReminders::class,
    ])
    ->create();
