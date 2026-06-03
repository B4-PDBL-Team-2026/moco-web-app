<?php

namespace App\Http\Controllers\Web\Auth;

use App\Domains\User\Actions\Auth\ForgotPasswordAction;
use App\Domains\User\Actions\Auth\HandleGoogleLoginUserAction;
use App\Domains\User\Actions\Auth\LoginUserAction;
use App\Domains\User\Actions\Auth\RegisterUserAction;
use App\Domains\User\Actions\Auth\ResetPasswordAction;
use App\Domains\User\Actions\Auth\VerifyEmailAction;
use App\Domains\User\Exceptions\UserBannedException;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\ForgotPasswordRequest;
use App\Http\Requests\User\Auth\LoginRequest;
use App\Http\Requests\User\Auth\RegisterRequest;
use App\Http\Requests\User\Auth\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Socialite;
use Throwable;

class AuthController extends Controller
{
    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function handleRegister(RegisterRequest $request, RegisterUserAction $action): RedirectResponse
    {
        $result = $action->execute($request->toDTO());

        auth()->login($result['user']);

        if ($result['requiresOnboarding']) {
            return redirect()->route('onboarding-show');
        }

        return redirect('/dashboard');
    }

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function handleLogin(LoginRequest $request, LoginUserAction $action): RedirectResponse
    {
        try {
            $result = $action->execute($request->toDTO());
        } catch (UserBannedException $e) {
            return redirect()->route('banned', [
                'bannedUntil' => $e->bannedUntil?->toIso8601String(),
            ]);
        }

        auth()->login($result['user']);

        if ($result['requiresOnboarding']) {
            return redirect()->route('onboarding-show');
        }

        return redirect('/dashboard');
    }

    public function showBanned(Request $request): Response
    {
        return Inertia::render('Auth/Banned', [
            'bannedUntil' => $request->query('bannedUntil'),
        ]);
    }

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

    public function showDeleteInfo(): Response
    {
        return Inertia::render('Auth/DeleteAccountInformation');
    }

    public function showForgetPassword(): Response
    {
        return Inertia::render('Auth/ForgetPassword');
    }

    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): Response|RedirectResponse
    {
        $result = $action->execute($request->validated('email'));

        if ($result['status'] === 'error') {
            return back()->withErrors(['email' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): Response|RedirectResponse
    {
        $result = $action->execute($request->toDTO());

        if ($result['status'] === 'error') {
            return back()->withErrors(['email' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(HandleGoogleLoginUserAction $action)
    {
        try {
            $providerUser = Socialite::driver('google')->user();

            $user = $action->execute($providerUser);

            Auth::login($user);

            return redirect()->route('dashboard');
        } catch (UserBannedException $e) {
            return redirect()->route('banned', [
                'bannedUntil' => $e->bannedUntil?->toIso8601String(),
            ]);
        } catch (Throwable $error) {
            \Log::error('[WEB] Auth Controller: SSO attempt fails '.$error->getMessage());

            return redirect()->route('login')->with('error', 'Gagal login pake google, coba ulang lagi ya.');
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
