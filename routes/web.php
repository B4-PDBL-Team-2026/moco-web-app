<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Route untuk update data per langkah
    Route::post('/onboarding/step-1', [OnboardingController::class, 'updateCycle'])->name('onboarding.cycle');
    Route::post('/onboarding/step-2', [OnboardingController::class, 'updateBalance'])->name('onboarding.balance');
});
