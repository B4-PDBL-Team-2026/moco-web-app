<?php

use App\Http\Controllers\Api\Budgeting\BudgetingController;
use App\Http\Controllers\Api\Budgeting\DashboardController;
use App\Http\Controllers\Api\Budgeting\OnboardingController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\FixedCost\FixedCostController;
use App\Http\Controllers\Api\Notification\DeviceController;
use App\Http\Controllers\Api\Notification\InAppNotificationController;
use App\Http\Controllers\Api\Transaction\TransactionController;
use App\Http\Controllers\Api\User\AuthController;
use App\Http\Controllers\Api\User\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/send-test-email', function () {
    try {
        Mail::raw('smtp server successfully configure', function ($message) {
            $message->to(config('services.accounts.email_trap'))
                ->subject('Test SMTP Server Success');
        });

        return 'email successfully sent';
    } catch (Exception $e) {
        return 'Failed to send email: '.$e->getMessage();
    }
});

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/login/google', 'loginWithGoogle');
    Route::post('/password/email', 'forgotPassword');
    Route::post('/password/reset', 'resetPassword');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/verify-email/request', 'sendVerificationEmail');
        Route::delete('/logout', 'logout');
        Route::delete('/user', 'destroy');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // Onboarding Endpoints
    Route::post('/onboarding', [OnboardingController::class, 'store'])->middleware('notOnboarded');

    // Category Endpoints
    Route::prefix('/category')->controller(CategoryController::class)->group(function () {
        Route::get('/system', 'indexSystemCategories');
        Route::prefix('/custom')->group(function () {
            Route::get('/', 'indexCustomCategories');
            Route::post('/', 'store');
            Route::patch('/{categoryId}', 'update');
            Route::delete('/{categoryId}', 'destroy');
        });
    });

    // Notification Routes
    Route::prefix('/notifications')->group(function () {
        // Device for push notifications
        Route::prefix('/device')->controller(DeviceController::class)->group(function () {
            Route::post('/', 'registerDevice');
            Route::get('/', 'getAllRegisteredDevice');
            Route::delete('/{id}', 'unregisterDevice');
        });

        Route::controller(InAppNotificationController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/test-push', 'testPush');
            Route::get('/unread-count', 'getUnreadTotal');
            Route::post('/{id}/read', 'markAsRead');
            Route::delete('/{id}', 'destroy');
        });
    });

    Route::middleware(['hasOnboarded', 'hasRecaculatedToday'])->group(function () {
        // Fixed Cost Endpoints
        Route::prefix('fixed-costs')->controller(FixedCostController::class)->group(function () {
            Route::get('/', 'index');
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

        // User Endpoints (profile + dashboard)
        Route::prefix('user')->group(function () {
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::patch('/profile', [ProfileController::class, 'update']);
            Route::get('/dashboard', [DashboardController::class, 'index']);
        });

        // Transaction Endpoints
        Route::controller(TransactionController::class)->prefix('transaction')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::prefix('/batch')->group(function () {
                Route::get('/{batchId}', 'showBatch');
                Route::post('/', 'storeBatch');
                Route::put('/{batchId}', 'updateBatch');
                Route::delete('/{batchId}', 'destroyBatch');
            });
            Route::get('/{transaction}', 'show');
            Route::put('/{transaction}', 'update');
            Route::delete('/{transaction}', 'destroy');
        });

        // Budgeting
        Route::prefix('settings')->controller(BudgetingController::class)->group(function () {
            Route::prefix('dailyLimit')->group(function () {
                Route::get('/', 'getUserDailyLimit');
                Route::patch('/', 'updateUserDailyLimit');
            });
        });
    });
});
