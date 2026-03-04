<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;


Route::middleware('auth:sanctum')->group(function () {

    // API untuk Gambar 1 & 2
    Route::prefix('onboarding')->group(function () {
        Route::post('/cycle', [OnboardingController::class, 'updateCycle']);
        Route::post('/balance', [OnboardingController::class, 'updateBalance']);
    });

    // API Fitur Utama (Hanya bisa diakses jika data onboarding lengkap)
    Route::middleware('onboarded')->group(function () {
        // Jika Controller belum ada, baris di bawah ini akan merah.
        // Kamu bisa ganti sementara dengan function anonim untuk testing:
        Route::get('/dashboard', function() { return response()->json(['msg' => 'Welcome!']); });
        Route::get('/transactions', function() { return response()->json(['msg' => 'History']); });
    });
});

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
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });
});
