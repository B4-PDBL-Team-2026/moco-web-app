<?php

use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Budgeting\DashboardController;
use App\Http\Controllers\Web\Budgeting\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/account/delete', 'showDeleteInfo');
        Route::get('/register', 'showRegister');
        Route::post('/register', 'handleRegister');
        Route::get('/login', 'showLogin')->name('login');
        Route::post('/login', 'handleLogin');
        Route::get('/forget-password', 'showForgetPassword');
        Route::post('/forget-password', 'forgotPassword');
        Route::get('/reset-password/{token}', 'showResetForm')->name('password.reset');
        Route::get('/verify-email/{id}/{hash}', 'verifyEmail')
            ->middleware(['signed'])
            ->name('verification.verify');
    });

});

Route::middleware(['auth'])->group(function () {
    // budgeting domain endpoints
    Route::prefix('/onboarding')->middleware(['notOnboarded'])->controller(OnboardingController::class)->group(function () {
        Route::get('/', 'showOnboarding')->name('onboarding-show');
        Route::post('/', 'completeOnboarding');
        Route::post('/preview', 'handleOnboarding');
    });

    Route::middleware(['hasOnboarded'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'showDashboard'])->name('dashboard');
    });

    // fixed costs domain endpoints
});
