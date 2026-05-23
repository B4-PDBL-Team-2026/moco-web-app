<?php

use App\Domains\User\Models\User;
use App\Http\Middleware\EnsureOnboardingIsCompleted;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

function makeJsonRequest(): Request
{
    return Request::create('/api/dummy', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
}

it('allows the request to proceed if user has completed onboarding', function () {
    $user = User::factory()->create()->forceFill(['has_onboarded' => true]);

    $request = makeJsonRequest();
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
    $user = User::factory()->create()->forceFill(['has_onboarded' => false]);

    $request = makeJsonRequest();
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
        'errors' => ['requiresOnboarding' => true],
    ])->toHaveKey('message');
});

it('blocks the request if there is no authenticated user', function () {
    $request = makeJsonRequest(); // no user resolver = $request->user() returns null

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
    expect($jsonResponse['errors']['requiresOnboarding'])->toBeTrue();
});
