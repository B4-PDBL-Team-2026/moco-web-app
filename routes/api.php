<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Category\CategoryController;
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
    Route::post('/onboarding', [OnboardingController::class, 'store']);

    // Category Endpoints
    Route::prefix('/category')->controller(CategoryController::class)->group(function () {
        Route::get('/system', 'getAllSystemCategory');
    });

    // Transaction Endpoints
    Route::controller(TransactionController::class)->prefix('transaction')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{transaction}', 'show');
        Route::put('/{transaction}', 'update');
        Route::delete('/{transaction}', 'destroy');
    });
});
