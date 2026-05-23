<?php

return [
    'notfound' => 'Waduh, data yang dicari nggak ketemu nih. Coba cek lagi ya!',
    'server_error' => 'Waduh, server Moco lagi batuk. Tim kita lagi otw benerin, ditunggu yak!',
    'authorization' => [
        'not_authenticated' => 'Sesi abis atau belum login nih. Login dulu yak!',
        'not_authorized' => 'Eits, fitur ini bukan buat kamu. Cek akses akunmu lagi, yuk!',
    ],
    'budgeting' => [
        'budget_setting_not_found' => 'Kamu belum setup pengaturan budgeting kamu, setup dulu yuk!',
        'balance_insufficient' => 'Eits, langkah ini bisa bikin saldo kamu minus. Biar catatan keuanganmu tetap sehat, sesuaikan nominal atau transaksinya dulu, ya!',
    ],
    'transaction' => [
        'future_date' => 'Sabar dulu! Tanggal transaksi nggak boleh di masa depan. Pakai tanggal hari ini atau sebelumnya, ya.',
        'not_found' => 'Data transaksi engga ditemuin nih, coba cek ulang ya',
    ],
    'category' => [
        'mismatch' => 'Kategorinya nggak nyambung, nih. Pastikan kategori yang kamu pilih sesuai dengan jenis transaksinya, ya!',
        'invalid_ownership' => 'Kamu cuma bisa pakai kategori buatanmu sendiri atau kategori bawaan dari Moco.',
        'cannot_modify_system' => 'Kayaknya kamu salah ngubah kategori deh, coba cek lagi ya!',
    ],
    'validation' => [
        'not_onboarded' => 'Beresin dulu step onboarding yuk!',
        'onboarded' => 'Kamu udah beresin step ini, ngga perlu ngulang lagi ya.',
    ],
    'fixed_cost' => [
        'name_empty' => 'Nama tagihannya jangan dikosongin dong, nanti kamu lupa ini tagihan apa.',
        'amount_invalid' => 'Nominal tagihan harus lebih dari nol dong. Masa gratis?',
        'due_day_weekly_invalid' => 'Pilih hari antara Senin (1) sampai Minggu (7) buat jadwal tagihan mingguanmu.',
        'due_day_monthly_invalid' => 'Pilih tanggal antara 1 sampai 31 buat jadwal tagihan bulananmu.',
        'cycle_mismatch' => 'Eh, tagihan bulanan nggak bisa masuk kalau pengaturan budget kamu mingguan. Disesuaikan lagi, ya!',
    ],
];
