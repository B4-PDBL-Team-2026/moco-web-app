<?php
namespace App\Http\Controllers\Api\Onboarding;
use App\Http\Controllers\Controller;
use App\Http\Requests\OnboardingRequest;
use App\Actions\ProcessOnboardingAction;


class OnboardingController extends Controller
{
    public function store(OnboardingRequest $request, ProcessOnboardingAction $action)
    {
        // Menjalankan logika di Action
        $result  = $action->execute($request->validated());
        $nominal = $result['limit_harian'];
        $format  = 'Rp ' . number_format($nominal, 0, ',', '.');
        // Mengembalikan Response (Langkah 4: Summary)
        return response()->json([
            'message' => 'Onboarding Berhasil',
            'data'    => [
                'summary' => [
                    'nominal_harian'   => $nominal,
                    'format_currency'  => $format,
                    'saldo_saat_ini'   => $result['saldo_utama'] ?? 0,
                    'saran_kategori'   => $result['rekomendasi'] ?? [],
                ],
            ],
        ]);

    }
}
