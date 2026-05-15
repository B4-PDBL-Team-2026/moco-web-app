<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute harus disetujui dulu ya.',
    'accepted_if' => ':attribute harus disetujui kalau :other isinya :value.',
    'active_url' => ':attribute harus berupa link URL yang bener nih.',
    'after' => ':attribute harus tanggal setelah :date ya.',
    'after_or_equal' => ':attribute harus tanggal setelah atau sama dengan :date.',
    'alpha' => ':attribute cuma boleh diisi huruf aja.',
    'alpha_dash' => ':attribute cuma boleh diisi huruf, angka, strip, dan garis bawah.',
    'alpha_num' => ':attribute cuma boleh diisi huruf dan angka.',
    'any_of' => 'Isian :attribute nggak valid nih.',
    'array' => ':attribute harus berupa kumpulan data (array).',
    'ascii' => ':attribute cuma boleh diisi karakter dan simbol alfanumerik biasa.',
    'before' => ':attribute harus tanggal sebelum :date ya.',
    'before_or_equal' => ':attribute harus tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':attribute harus punya :min sampai :max item.',
        'file' => 'Ukuran file :attribute harus di antara :min sampai :max kilobyte.',
        'numeric' => ':attribute harus di antara :min sampai :max.',
        'string' => 'Panjang teks :attribute harus di antara :min sampai :max karakter.',
    ],
    'boolean' => ':attribute cuma boleh diisi bener (true) atau salah (false).',
    'can' => ':attribute berisi nilai yang nggak diizinkan nih.',
    'confirmed' => 'Konfirmasi :attribute nggak cocok nih. Cek lagi yuk!',
    'contains' => ':attribute butuh isian yang spesifik, tapi belum ada nih.',
    'current_password' => 'Password kamu salah nih.',
    'date' => ':attribute bukan format tanggal yang bener.',
    'date_equals' => ':attribute harus tanggal yang sama persis kayak :date.',
    'date_format' => 'Format :attribute nggak cocok sama format :format.',
    'decimal' => ':attribute harus punya :decimal angka di belakang koma.',
    'declined' => ':attribute harus ditolak.',
    'declined_if' => ':attribute harus ditolak kalau :other isinya :value.',
    'different' => ':attribute dan :other harus beda ya isinya.',
    'digits' => ':attribute harus pas :digits angka.',
    'digits_between' => ':attribute harus di antara :min sampai :max angka.',
    'dimensions' => 'Dimensi gambar :attribute nggak pas nih.',
    'distinct' => 'Ada isi :attribute yang kembar. Bikin beda-beda ya.',
    'doesnt_contain' => ':attribute nggak boleh diisi ini ya: :values.',
    'doesnt_end_with' => ':attribute nggak boleh diakhiri sama: :values.',
    'doesnt_start_with' => ':attribute nggak boleh diawali sama: :values.',
    'email' => 'Format alamat email :attribute nggak valid nih.',
    'encoding' => ':attribute harus di-encode pakai :encoding.',
    'ends_with' => ':attribute harus diakhiri salah satu dari ini: :values.',
    'enum' => 'Pilihan :attribute nggak kita kenalin nih.',
    'exists' => ':attribute yang dipilih nggak ketemu di sistem kita.',
    'extensions' => 'Ekstensi file :attribute harus salah satu dari: :values.',
    'file' => ':attribute harus berupa file.',
    'filled' => ':attribute nggak boleh dibiarin kosong.',
    'gt' => [
        'array' => ':attribute harus punya lebih dari :value item.',
        'file' => 'Ukuran file :attribute harus lebih gede dari :value kilobyte.',
        'numeric' => ':attribute harus lebih gede dari :value dong.',
        'string' => 'Teks :attribute harus lebih panjang dari :value karakter.',
    ],
    'gte' => [
        'array' => ':attribute minimal harus punya :value item.',
        'file' => 'Ukuran file :attribute minimal banget :value kilobyte ya.',
        'numeric' => ':attribute minimal banget :value ya.',
        'string' => 'Teks :attribute minimal harus :value karakter dong.',
    ],
    'hex_color' => 'Format warna hex :attribute nggak valid nih.',
    'image' => ':attribute harus berupa gambar ya.',
    'in' => 'Pilihan :attribute cuma ada :values nih. Dipilih ya!',
    'in_array' => 'Isian :attribute nggak ketemu di :other.',
    'in_array_keys' => ':attribute minimal harus punya salah satu key ini: :values.',
    'integer' => ':attribute harus berupa angka bulat.',
    'ip' => ':attribute harus berupa alamat IP yang valid.',
    'ipv4' => ':attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => ':attribute harus berupa alamat IPv6 yang valid.',
    'json' => ':attribute harus berupa string JSON yang valid.',
    'list' => ':attribute harus berupa daftar (list).',
    'lowercase' => ':attribute harus pakai huruf kecil semua ya.',
    'lt' => [
        'array' => ':attribute nggak boleh punya :value item atau lebih.',
        'file' => 'Ukuran file :attribute harus di bawah :value kilobyte.',
        'numeric' => ':attribute harus kurang dari :value ya.',
        'string' => 'Teks :attribute harus di bawah :value karakter.',
    ],
    'lte' => [
        'array' => ':attribute maksimal banget punya :value item.',
        'file' => 'Ukuran file :attribute maksimal banget :value kilobyte ya.',
        'numeric' => ':attribute maksimal banget :value ya.',
        'string' => 'Teks :attribute maksimal banget :value karakter ya.',
    ],
    'mac_address' => ':attribute harus berupa MAC address yang valid.',
    'max' => [
        'array' => ':attribute maksimal cuma boleh punya :max item.',
        'file' => 'Ukuran file :attribute jangan lebih dari :max kilobyte dong.',
        'numeric' => ':attribute nggak boleh lebih dari :max.',
        'string' => 'Teks :attribute maksimal :max karakter ya, jangan kepanjangan.',
    ],
    'max_digits' => ':attribute nggak boleh lebih dari :max angka.',
    'mimes' => 'File :attribute tipenya harus: :values.',
    'mimetypes' => 'File :attribute tipenya harus: :values.',
    'min' => [
        'array' => ':attribute minimal harus punya :min item.',
        'file' => 'Ukuran file :attribute minimal harus :min kilobyte.',
        'numeric' => ':attribute minimal banget :min ya.',
        'string' => 'Teks :attribute minimal harus :min karakter dong.',
    ],
    'min_digits' => ':attribute minimal harus diisi :min angka.',
    'missing' => 'Isian :attribute harusnya nggak ada di sini.',
    'missing_if' => 'Isian :attribute harusnya diilangin kalau :other isinya :value.',
    'missing_unless' => 'Isian :attribute harusnya diilangin, kecuali :other isinya :value.',
    'missing_with' => 'Isian :attribute harusnya diilangin kalau ada :values.',
    'missing_with_all' => 'Isian :attribute harusnya diilangin kalau ada :values semua.',
    'multiple_of' => ':attribute harus kelipatan dari :value dong.',
    'not_in' => 'Pilihan :attribute nggak bisa dipakai nih.',
    'not_regex' => 'Format :attribute nggak bener nih.',
    'numeric' => ':attribute harus berupa angka ya.',
    'password' => [
        'letters' => ':attribute minimal harus diisi huruf.',
        'mixed' => ':attribute harus ada kombinasi huruf besar dan kecil biar aman.',
        'numbers' => ':attribute minimal harus ada angkanya.',
        'symbols' => ':attribute minimal harus pakai simbol.',
        'uncompromised' => 'Wah, :attribute ini udah pernah bocor di internet. Ganti pakai yang lain yang lebih aman yuk!',
    ],
    'present' => 'Isian :attribute harus diadain nih.',
    'present_if' => 'Isian :attribute harus diadain kalau :other isinya :value.',
    'present_unless' => 'Isian :attribute harus diadain, kecuali :other isinya :value.',
    'present_with' => 'Isian :attribute harus diadain kalau ada :values.',
    'present_with_all' => 'Isian :attribute harus diadain kalau ada :values semua.',
    'prohibited' => 'Dilarang ngisi :attribute nih.',
    'prohibited_if' => 'Dilarang ngisi :attribute kalau :other isinya :value.',
    'prohibited_if_accepted' => 'Dilarang ngisi :attribute kalau :other udah disetujui.',
    'prohibited_if_declined' => 'Dilarang ngisi :attribute kalau :other ditolak.',
    'prohibited_unless' => 'Dilarang ngisi :attribute, kecuali :other isinya salah satu dari :values.',
    'prohibits' => 'Kalau kamu ngisi :attribute, kamu nggak boleh ngisi :other ya.',
    'regex' => 'Format isian :attribute nggak sesuai nih.',
    'required' => ':attribute nggak boleh dikosongin dong, yuk diisi!',
    'required_array_keys' => ':attribute wajib diisi isian buat: :values.',
    'required_if' => ':attribute harus diisi kalau :other isinya :value.',
    'required_if_accepted' => ':attribute harus diisi kalau :other disetujui.',
    'required_if_declined' => ':attribute harus diisi kalau :other ditolak.',
    'required_unless' => ':attribute harus diisi, kecuali :other isinya salah satu dari :values.',
    'required_with' => ':attribute harus diisi kalau :values juga diisi.',
    'required_with_all' => ':attribute harus diisi kalau semua :values diisi.',
    'required_without' => ':attribute wajib diisi kalau :values lagi kosong.',
    'required_without_all' => ':attribute wajib banget diisi kalau nggak ada satupun :values yang diisi.',
    'same' => ':attribute harus sama persis kayak :other.',
    'size' => [
        'array' => ':attribute harus pas diisi :size item.',
        'file' => 'Ukuran file :attribute harus pas di :size kilobyte.',
        'numeric' => ':attribute harus pas di angka :size.',
        'string' => 'Teks :attribute harus pas di :size karakter.',
    ],
    'starts_with' => ':attribute harus diawali sama salah satu dari ini: :values.',
    'string' => ':attribute harus berupa teks ya.',
    'timezone' => ':attribute harus berupa zona waktu yang bener.',
    'unique' => 'Wah, :attribute ini udah ada yang punya. Cari yang lain yuk!',
    'uploaded' => 'File :attribute gagal di-upload nih.',
    'uppercase' => ':attribute harus pakai huruf besar semua ya.',
    'url' => ':attribute harus berupa URL yang bener.',
    'ulid' => ':attribute harus berupa ULID yang bener.',
    'uuid' => ':attribute harus berupa UUID yang bener.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'ceilingLimit' => [
            'gte' => 'Waduh, limit atas nggak boleh lebih kecil dari limit bawah ya. Yuk disesuaikan!',
        ],
        'fixedCosts.*.amount' => [
            'gt' => 'Nominal tagihannya harus lebih besar dari nol dong. Masa gratis?',
        ],
        'fixedCosts.*.cycleType' => [
            'mismatch' => 'Eh, tagihan bulanan nggak bisa masuk kalau pengaturan budget kamu mingguan. Disesuaikan lagi, ya!',
        ],
        'fixedCosts.*.dueDay' => [
            'weekly_invalid' => 'Pilih hari antara Senin (1) sampai Minggu (7) buat jadwal tagihan mingguanmu.',
            'monthly_invalid' => 'Pilih tanggal antara 1 sampai 31 buat jadwal tagihan bulananmu.',
        ],
        'transactionAt' => [
            'before_or_equal' => 'Sabar dulu! Tanggal transaksi nggak boleh di masa depan. Pakai waktu saat ini atau sebelumnya, ya.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'budgetCycle' => 'siklus budget',
        'initialBalance' => 'saldo awal',
        'flooringLimit' => 'limit bawah',
        'ceilingLimit' => 'limit atas',
        'fixedCosts' => 'daftar tagihan rutin',
        'fixedCosts.*.name' => 'nama tagihan',
        'fixedCosts.*.amount' => 'nominal tagihan',
        'fixedCosts.*.categoryId' => 'kategori tagihan',
        'fixedCosts.*.dueDay' => 'hari/tanggal tagihan',
        'fixedCosts.*.cycleType' => 'siklus tagihan',
        'name' => 'nama',
        'icon' => 'ikon',
        'type' => 'tipe',
        'amount' => 'nominal',
        'categoryId' => 'kategori',
        'perPage' => 'jumlah data per halaman',
        'page' => 'halaman',
        'keyword' => 'kata kunci pencarian',
        'startDate' => 'tanggal mulai',
        'endDate' => 'tanggal akhir',
        'status' => 'status',
        'note' => 'catatan',
        'cycleType' => 'siklus',
        'dueDay' => 'hari/tanggal jatuh tempo',
        'isActive' => 'status aktif',
        'deviceId' => 'ID perangkat',
        'deviceType' => 'tipe perangkat',
        'fcmToken' => 'token notifikasi',
        'month' => 'bulan',
        'year' => 'tahun',
        'search' => 'kata kunci pencarian',
        'transactionAt' => 'tanggal transaksi',
        'email' => 'alamat email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'token' => 'token reset',
        'display_name' => 'nama panggilan',
        'avatar_url' => 'link foto profil',
        'currency' => 'mata uang',
        'locale' => 'bahasa',
    ],

];
