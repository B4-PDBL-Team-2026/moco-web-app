<?php
// app/Traits/WebResponse.php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;

trait WebResponse
{
    /**
     * Redirect dengan pesan sukses.
     */
    protected function redirectSuccess(string $route, string $message): RedirectResponse
    {
        return redirect()->route($route)->with('success', $message);
    }

    /**
     * Redirect kembali dengan pesan error.
     */
    protected function redirectError(string $message): RedirectResponse
    {
        return back()->with('error', $message)->withInput();
    }

    /**
     * Redirect ke halaman tertentu dengan pesan sukses.
     */
    protected function redirectTo(string $url, string $message): RedirectResponse
    {
        return redirect($url)->with('success', $message);
    }
}