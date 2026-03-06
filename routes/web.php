<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Auth\ResetPasswordController;

Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
