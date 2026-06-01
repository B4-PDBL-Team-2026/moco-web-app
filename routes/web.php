<?php

use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Budgeting\DashboardController;
use App\Http\Controllers\Web\Budgeting\OnboardingController;
use App\Http\Controllers\Web\Category\CategoryController;
use App\Http\Controllers\Web\FixedCost\FixedCostController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::middleware(['notAuthenticated'])->group(function () {
            Route::get('/register', 'showRegister');
            Route::post('/register', 'handleRegister');
            Route::get('/login', 'showLogin')->name('login');
            Route::post('/login', 'handleLogin');
            Route::prefix('/oauth/google')->group(function () {
                Route::get('/redirect', 'redirectToGoogle');
                Route::get('/callback', 'callback');
            });
        });
        Route::get('/account/delete', 'showDeleteInfo');
        Route::get('/forget-password', 'showForgetPassword');
        Route::post('/forget-password', 'forgotPassword');
        Route::get('/reset-password/{token}', 'showResetForm')->name('password.reset');
        Route::post('/reset-password', 'resetPassword');
        Route::get('/verify-email/{id}/{hash}', 'verifyEmail')
            ->middleware(['signed'])
            ->name('verification.verify');
        Route::delete('/logout', 'logout')->middleware('auth');
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
    Route::prefix('/fixed-costs')->controller(FixedCostController::class)->group(function () {
        Route::prefix('/occurrences')->group(function () {
            Route::get('/', 'indexOccurrence')
                ->name('fixed-costs.occurrences.index');
            Route::post('/{occurrenceId}/confirm-payment', 'confirmPayment')
                ->name('fixed-costs.occurrences.confirm-payment');
            Route::post('/{occurrenceId}/cancel-payment', 'cancelPayment')
                ->name('fixed-costs.occurrences.cancel-payment');
            Route::post('/{occurrenceId}/skip', 'skipOccurrence')
                ->name('fixed-costs.occurrences.skip');
        });

        Route::prefix('/templates')->group(function () {
            Route::get('/', 'index')->name('fixed-costs.index');
            Route::post('/', 'store')->name('fixed-costs.store');
            Route::patch('/{templateId}', 'update')->name('fixed-costs.update');
            Route::delete('/{templateId}', 'destroy')->name('fixed-costs.destroy');
        });
    });

    // categories domain endpoints
    Route::prefix('/categories')->controller(CategoryController::class)->group(function () {
        Route::get('/', 'index')->name('categories.index');
        Route::post('/', 'store')->name('categories.store');
        Route::patch('/{categoryId}', 'update')->name('categories.update');
        Route::delete('/{categoryId}', 'destroy')->name('categories.destroy');
    });
});
