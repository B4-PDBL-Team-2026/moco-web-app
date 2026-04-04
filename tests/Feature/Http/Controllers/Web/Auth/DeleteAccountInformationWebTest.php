<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

// Accessibility — no auth required

test('page is accessible without authentication', function () {
    $this->get('auth/account/delete')->assertOk();
});

test('page is accessible when authenticated', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->get('auth/account/delete')->assertOk();
});

// Inertia component

test('renders the correct Inertia component', function () {
    $this->get('auth/account/delete')
        ->assertInertia(
            fn ($page) => $page->component('Auth/DeleteAccountInformation')
        );
});

test('renders with no required props (static page)', function () {
    $this->get('auth/account/delete')
        ->assertInertia(
            fn ($page) => $page->component('Auth/DeleteAccountInformation')
        );
});

// HTTP semantics

test('returns 200 status code', function () {
    $this->get('auth/account/delete')->assertStatus(200);
});

test('POST method is not allowed on this route', function () {
    $this->post('auth/account/delete')->assertMethodNotAllowed();
});

test('DELETE method is not allowed on this route', function () {
    $this->delete('auth/account/delete')->assertMethodNotAllowed();
});
