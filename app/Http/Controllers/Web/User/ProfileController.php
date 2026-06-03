<?php

namespace App\Http\Controllers\Web\User;

use App\Domains\Budgeting\Actions\UpdateDailyLimitAction;
use App\Domains\Budgeting\DTOs\UpdateDailyLimitData;
use App\Domains\User\Actions\Auth\DeleteUserAction;
use App\Domains\User\Actions\Auth\SendEmailVerificationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $budgetSetting = $user->budgetSetting;

        return Inertia::render('Settings/ProfileSettings', [
            'budget_setting' => [
                'min_allocation' => $budgetSetting->flooring_limit ?? 0,
                'max_allocation' => $budgetSetting->ceiling_limit ?? 0,
            ],
        ]);
    }

    public function updateBudget(Request $request, UpdateDailyLimitAction $action)
    {
        $validated = $request->validate([
            'min_allocation' => 'required|numeric',
            'max_allocation' => 'required|numeric',
        ]);

        $action->execute(
            $request->user(),
            new UpdateDailyLimitData(
                flooringLimit: (string) $validated['min_allocation'],
                ceilingLimit: (string) $validated['max_allocation']
            )
        );

        return redirect()->back()->with('success', 'Budget berhasil diperbarui!');
    }

    public function deleteAccount(Request $request, DeleteUserAction $action)
    {
        // Validasi password sebelum hapus akun
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Logout user untuk mematikan sesi
        Auth::logout();

        // Eksekusi Action untuk menghapus seluruh data user
        $action->execute($user);

        // Bersihkan cache/sesi browser
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function sendVerification(Request $request, SendEmailVerificationAction $action)
    {
        // Eksekusi Action untuk mengirim email
        $action->execute($request->user());

        return redirect()->back()->with('message', 'Email verifikasi terkirim!');
    }
}
