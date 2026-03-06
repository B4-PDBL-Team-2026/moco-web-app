<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/AuthPassword', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }
}
