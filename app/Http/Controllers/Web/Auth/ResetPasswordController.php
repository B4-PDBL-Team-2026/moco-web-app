<?php

namespace App\Http\Controllers\Web\Auth;

use Inertia\Inertia;
use Illuminate\Http\Request;
use \App\Http\Controllers\Controller;
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
