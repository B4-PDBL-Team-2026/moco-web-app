<?php

namespace Tests\Unit\Actions\Onboarding;

use Tests\TestCase;
use App\Actions\ProcessOnboardingAction;
use App\Enums\SiklusUang;
use App\Enums\FixedCostStatus;

class ProcessOnboardingActionTest extends TestCase
{
    public function test_kalkulasi_limit_harian_dan_saldo_utama_akurat()
    {
        $action = new ProcessOnboardingAction();


        $data = [
            'siklus' => 'bulanan',
            'nominal_uang_saku' => 1500000,
            'fixed_costs' => [
                ['nama' => 'Kost', 'nominal' => 200000, 'status' => 'in'],
                ['nama' => 'Tabungan', 'nominal' => 100000, 'status' => 'out'],
            ]
        ];

        $result = $action->execute($data);

        $this->assertEquals(40000, $result['limit_harian']);

        // Perhitungan Saldo Utama: 1.500.000 - 200.000 (Hanya yang 'IN') = 1.300.000
        $this->assertEquals(1300000, $result['saldo_utama']);
    }
}
