<?php

test('kalkulasi jatah harian harus benar', function () {
    // Data dummy: Uang saku 1.5jt, Fixed Cost (Sewa) 300rb, Siklus Bulanan (30 hari)
    // Rumus: (1.500.000 - 300.000) / 30 = 40.000
    $data = [
        'siklus' => 'bulanan',
        'nominal_uang_saku' => 1500000,
        'fixed_costs' => [
            [
                'nama' => 'Sewa Kos',
                'nominal' => 300000,
                'status' => 'in'
            ]
        ]
    ];

    $response = $this->postJson('/api/onboarding', $data);

    $response->assertStatus(200)
             ->assertJsonPath('data.summary.nominal_harian', 40000)
             ->assertJsonPath('data.summary.format_currency', 'Rp 40.000');
});

test('user tidak bisa lanjut jika uang saku nol', function () {
    $response = $this->postJson('/api/onboarding', [
        'siklus' => 'mingguan',
        'nominal_uang_saku' => 0,
    ]);

    $response->assertStatus(422); // Harus gagal validasi
});
