<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\CycleType;

class OnboardingController extends Controller
{
    /**
     * Menyimpan Langkah 1: Siklus Keuangan
     */
    public function updateCycle(Request $request)
    {
        $request->validate([
            'cycle_type' => 'required|in:weekly,monthly',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->update([
            'cycle_type' => $request->cycle_type,
            'cycle_start' => now(),
        ]);

        return response()->json(['status' => 'success']);
    } // Kurung tutup fungsi updateCycle

    public function updateBalance(Request $request)
    {
        $request->validate([
            'balance' => 'required|numeric|min:0',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->update([
            'balance' => $request->balance,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Saldo awal berhasil disimpan.',
            'next_step' => 'fixed_costs'
        ]);
    }
}
