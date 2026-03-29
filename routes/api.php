<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\FixedCost\FixedCostController;
use App\Http\Controllers\Api\Onboarding\OnboardingController;
use App\Http\Controllers\Api\Profile\ProfileController;
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

    // Fixed Cost Endpoints
    Route::prefix('fixed-costs')->controller(FixedCostController::class)->group(function () {
        Route::post('/', 'store');
        Route::patch('/{templateId}', 'update');
        Route::delete('/{templateId}', 'destroy');

        Route::prefix('occurrences')->group(function () {
            Route::get('/', 'indexOccurrences');
            Route::post('/{occurrenceId}/confirm', 'confirmPayment');
            Route::post('/{occurrenceId}/cancel', 'cancelPayment');
            Route::patch('/{occurrenceId}/amount', 'updateOccurrenceAmount');
            Route::patch('/{occurrenceId}/metadata', 'updateOccurrenceMetadata');
        });
    });

    // Transaction Endpoints
    Route::controller(TransactionController::class)->prefix('transaction/transactions')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{transaction}', 'show');
            Route::put('/{transaction}', 'update');
            Route::delete('/{transaction}', 'destroy');
        });

    // User Endpoints (profile + dashboard)
    Route::prefix('user')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
    });

    // User Endpoints (profile + dashboard)
    Route::prefix('user')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
    });
});
