<?php

use Inertia\Testing\AssertableInertia as Assert;

test('it can render reset password page correctly', function () {
    $this->withoutVite();

    $response = $this->get('auth/reset-password/token-rahasia-123?email=test@moco.com');

    $response->assertStatus(200);

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Auth/ResetPassword')
        ->where('token', 'token-rahasia-123')
        ->where('email', 'test@moco.com')
    );
});
