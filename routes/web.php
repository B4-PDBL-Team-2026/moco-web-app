<?php

use App\Http\Controllers\Web\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
