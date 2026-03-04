<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOnboarding
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Cek apakah user sudah login dan butuh onboarding
        if (Auth::check() && $user->isRequireOnboarding()) {

            // Hindari redirect berulang (infinite loop) jika sudah di halaman onboarding
            if (!$request->is('onboarding*')) {
                return redirect()->route('onboarding.step1');
            }
        }

        return $next($request);
    }
}
