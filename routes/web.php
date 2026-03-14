<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;

Route::get('/', function () {
    return "Home Page"; // Mengembalikan teks, bukan view
});
