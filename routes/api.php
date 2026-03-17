<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Onboarding\OnboardingController;
use App\Http\Controllers\Api\Transaction\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::delete('/logout', 'logout')->middleware('auth:sanctum');
    Route::post('/password/email', 'forgotPassword');
    Route::post('/password/reset', 'resetPassword');
    Route::get('/verify-email/{id}/{hash}', 'verifyEmail')
        ->middleware(['signed'])
        ->name('verification.verify');
});

Route::middleware('auth:sanctum')->group(function () {
    // Onboarding Endpoints
    Route::controller(OnboardingController::class)->prefix('onboarding')->group(function () {
        Route::get('/', 'show');
        Route::post('/', 'store');
    });

    // Transaction Endpoints
    Route::controller(TransactionController::class)->prefix('transaction')->group(function () {
        Route::get('/transactions', 'index');
        Route::post('/transactions', 'store');
        Route::get('/transactions/{transaction}', 'show');
        Route::put('/transactions/{transaction}', 'update');
        Route::delete('/transactions/{transaction}', 'destroy');
    });
});
