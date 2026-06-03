<?php

use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Budgeting\DashboardController;
use App\Http\Controllers\Web\Budgeting\OnboardingController;
use App\Http\Controllers\Web\Budgeting\TransactionController;
use App\Http\Controllers\Web\Category\CategoryController;
use App\Http\Controllers\Web\FixedCost\FixedCostController;
use App\Http\Controllers\Web\User\ProfileController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\AdminUsersController;
use App\Http\Controllers\Web\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Web\Feedback\FeedbackController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
        Route::get('/banned', 'showBanned')->name('banned');
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
    Route::middleware('isUser')->group(function () {

        Route::prefix('/onboarding')->middleware(['notOnboarded'])->controller(OnboardingController::class)->group(function () {
            Route::get('/', 'showOnboarding')->name('onboarding-show');
            Route::post('/', 'completeOnboarding');
            Route::post('/preview', 'handleOnboarding');
        });

        Route::middleware(['hasOnboarded'])->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'showDashboard'])->name('dashboard');
            Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
            Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
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
    });

    // SETTINGS:
    Route::get('/settings', [ProfileController::class, 'edit'])->name('settings');
    Route::post('/settings/update-budget', [ProfileController::class, 'updateBudget'])->name('settings.update-budget');
    Route::delete('/settings/delete-account', [ProfileController::class, 'deleteAccount'])->name('settings.delete-account');
    Route::post('/settings/send-verification', [ProfileController::class, 'sendVerification'])->name('settings.send-verification');

    // ADMIN
    Route::prefix('/admin')->middleware(['isAdmin'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::prefix('/feedback')->controller(AdminFeedbackController::class)->group(function () {
            Route::get('/', 'index')->name('admin.feedback.index');
            Route::post('/{feedback}/respond', 'respond')->name('admin.feedback.respond');
        });

        Route::prefix('/users')->controller(AdminUsersController::class)->group(function () {
            Route::get('/', 'index')->name('admin.users.index');
            Route::put('/{user}', 'update')->name('admin.users.update');
            Route::post('/{user}/force-logout', 'forceLogout')->name('admin.users.force-logout');
            Route::delete('/{user}', 'destroy')->name('admin.users.destroy');
        });
    });

    // CATEGORIES
    Route::prefix('/categories')->controller(CategoryController::class)->group(function () {
        Route::get('/', 'index')->name('categories.index');
        Route::post('/', 'store')->name('categories.store');
        Route::patch('/{categoryId}', 'update')->name('categories.update');
        Route::delete('/{categoryId}', 'destroy')->name('categories.destroy');
    });

    // FEEDBACK
    Route::controller(FeedbackController::class)->group(function () {
        Route::get('/feedback', 'create')->name('feedback.create');
        Route::post('/feedback', 'store')->name('feedback.store');
    });
});

Route::middleware('throttle:analytics')->controller(LandingPageAnalyticController::class)->group(function () {
    Route::post('/analytics/visit', 'trackVisit');
    Route::post('/analytics/scroll', 'trackScroll');
});
