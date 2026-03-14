<?php
namespace App\Actions;

use App\Enums\SiklusUang;
use App\Enums\FixedCostStatus;

class ProcessOnboardingAction
{
    public function execute(array $data)
    {
        $siklus = SiklusUang::from($data['siklus']);
        $uangSaku = $data['nominal_uang_saku'];

        $totalFixedCost = 0;
        $totalMemotongSaldo = 0;

        foreach ($data['fixed_costs'] as $item) {
            $nominal = $item['nominal'];
            $status = FixedCostStatus::from($item['status']);

            $totalFixedCost += $nominal;

            // Jika status In, nominal langsung memotong Saldo Utama
            if ($status === FixedCostStatus::IN) {
                $totalMemotongSaldo += $nominal;
            }
        }

        // Hitung Limit Harian
        $limitHarian = ($uangSaku - $totalFixedCost) / $siklus->jumlahHari();

        // Hitung Saldo Utama (Uang Saku - pengeluaran status 'In')
        $saldoUtama = $uangSaku - $totalMemotongSaldo;

        return [
            'limit_harian' => $limitHarian,
            'saldo_utama' => $saldoUtama,
            'siklus_teks' => $siklus->value,
            'rekomendasi' => $rekomendasi??[],
        ];
    }
}

