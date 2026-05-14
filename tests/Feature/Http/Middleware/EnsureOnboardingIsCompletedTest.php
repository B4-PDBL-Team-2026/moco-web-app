<?php

use App\Domains\User\Models\User;
use App\Http\Middleware\EnsureOnboardingIsCompleted;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

it('allows the request to proceed if user has completed onboarding', function () {
    $user = User::factory()->create();

    $user->setAttribute('hasOnboarded', true);

    $request = Request::create('/api/dummy', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new EnsureOnboardingIsCompleted;

    $nextCalled = false;
    $next = function () use (&$nextCalled) {
        $nextCalled = true;

        return new HttpResponse('Passed');
    };

    $response = $middleware->handle($request, $next);

    expect($nextCalled)->toBeTrue()
        ->and($response->getContent())->toBe('Passed');
});

it('blocks the request and returns 403 if user has not completed onboarding', function () {
    $user = User::factory()->create();

    $user->setAttribute('hasOnboarded', false);

    $request = Request::create('/api/dummy', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new EnsureOnboardingIsCompleted;

    $nextCalled = false;
    $next = function () use (&$nextCalled) {
        $nextCalled = true;

        return new HttpResponse('Passed');
    };

    $response = $middleware->handle($request, $next);

    expect($nextCalled)->toBeFalse()
        ->and($response->getStatusCode())->toBe(Response::HTTP_FORBIDDEN);

    $jsonResponse = json_decode($response->getContent(), true);

    expect($jsonResponse)->toMatchArray([
        'success' => false,
        'errors' => [
            'requires_onboarding' => true,
        ],
    ])->toHaveKey('message');
});

it('blocks the request if there is no authenticated user', function () {
    $request = Request::create('/api/dummy', 'GET');

    $middleware = new EnsureOnboardingIsCompleted;

    $nextCalled = false;
    $next = function () use (&$nextCalled) {
        $nextCalled = true;

        return new HttpResponse('Passed');
    };

    $response = $middleware->handle($request, $next);

    expect($nextCalled)->toBeFalse()
        ->and($response->getStatusCode())->toBe(Response::HTTP_FORBIDDEN);

    $jsonResponse = json_decode($response->getContent(), true);

    expect($jsonResponse['errors']['requires_onboarding'])->toBeTrue();
});
