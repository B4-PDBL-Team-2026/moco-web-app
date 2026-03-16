<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Onboarding\OnboardingController;
use App\Http\Controllers\Api\Transaction\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/password/email', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
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
