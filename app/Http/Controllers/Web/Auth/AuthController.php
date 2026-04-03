<?php

namespace App\Http\Controllers\Web\Auth;

use App\Domains\Auth\Actions\VerifyEmailAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Verify the user's email address using the signed route hash.
     */
    public function verifyEmail(Request $request, VerifyEmailAction $action): Response
    {
        $user = User::query()->findOrFail($request->route('id'));

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return Inertia::render('Auth/EmailVerification',
                [
                    'status' => 'error',
                    'message' => 'Verification link is invalid.',
                ]);
        }

        $result = $action->execute($user);

        return Inertia::render('Auth/EmailVerification', [
            'status' => $result['status'],
            'message' => $result['message'],
        ]);
    }
}
