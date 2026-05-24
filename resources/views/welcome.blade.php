<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOCO - Kuasai Keuanganmu</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white font-sans overflow-x-hidden">

{{-- NAVBAR --}}
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-sm border-b border-gray-100">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <img src="/logo.png" alt="MOCO" class="h-8" onerror="this.style.display='none'">
            <span class="text-primary font-bold text-xl tracking-tight">MOCO</span>
        </div>
        <div class="hidden md:flex items-center gap-8">
            <a href="#fitur" class="text-gray-600 hover:text-primary text-sm font-medium transition-colors">Fitur</a>
            <a href="#cara-kerja" class="text-gray-600 hover:text-primary text-sm font-medium transition-colors">Cara Kerja</a>
            <a href="#testimoni" class="text-gray-600 hover:text-primary text-sm font-medium transition-colors">Testimoni</a>
        </div>
        <div class="flex items-center gap-3">
            <a href="/auth/login" class="text-primary font-semibold text-sm px-5 py-2 rounded-xl border border-primary hover:bg-primary-light transition-colors">Masuk</a>
            <a href="/auth/register" class="btn bg-secondary text-white font-semibold text-sm px-5 py-2 rounded-xl hover:bg-secondary/90 transition-colors">Daftar</a>
        </div>
    </div>
</nav>

{{-- HERO SECTION --}}
<section class="pt-32 pb-20 px-6 max-w-6xl mx-auto">
    <div class="grid md:grid-cols-2 gap-12 items-center">
        <div>
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 leading-tight mb-5">
                Kuasai<br>Keuanganmu<br>dengan <span class="text-primary">MOCO</span>
            </h1>
            <p class="text-gray-500 text-base leading-relaxed mb-8 max-w-md">
                Atur jatah harian, catat pengeluaran lewat suara atau foto struk, pantau biaya tetap, dan capai tujuan finansialmu semua dalam satu aplikasi.
            </p>
            <a href="https://play.google.com/store/apps/details?id=com.moco.moneycontrol&hl=en_US" class="inline-flex items-center gap-3 bg-primary text-white font-semibold px-6 py-3 rounded-2xl hover:bg-primary/90 transition-colors text-sm">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.76c.3.17.64.24.99.2l13.5-7.79-2.89-2.89L3.18 23.76zM.69 1.67C.27 2.1 0 2.78 0 3.67v16.67c0 .89.27 1.57.69 2l.11.1 9.34-9.34v-.22L.8 3.55.69 1.67zM20.23 9.67l-2.56-1.48-3.23 3.23 3.24 3.24 2.56-1.48c.73-.42.73-1.1 0-1.52l.01.01zM4.17.24L17.67 8.03l-2.89 2.89L3.18.23c.35-.04.7.03.99.01z"/></svg>
                Unduh di Google Play
            </a>
        </div>
        <div class="relative flex justify-center">
            <img src="{{asset('/hero-section.png')}}" alt="MOCO App Preview" class="w-full max-w-md drop-shadow-xl">
        </div>
    </div>
</section>

{{-- FITUR UNGGULAN --}}
<section id="fitur" class="py-20 px-6 bg-gray-50">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-14">
            <span class="text-xs font-semibold text-primary bg-primary-light px-3 py-1 rounded-full uppercase tracking-widest">Fitur Unggulan</span>
            <h2 class="text-3xl font-bold text-gray-900 mt-4 mb-3">Satu Aplikasi, Ribuan Cara Hemat</h2>
            <p class="text-gray-500 text-sm">Biarkan MOCO membantumu!</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <div class="w-11 h-11 rounded-xl bg-primary-light flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Budgeting Cerdas</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Atur jatah harian secara otomatis berdasarkan saldo dan tagihan mendatang agar tabungan maksimal.</p>
            </div>
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <div class="w-11 h-11 rounded-xl bg-secondary-light flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Pencatatan Cepat</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Catat pengeluaran hanya dalam hitungan detik lewat suara atau struk belanja dengan bantuan AI.</p>
            </div>
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <div class="w-11 h-11 rounded-xl bg-primary-light flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Pengingat Pintar</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Jangan lewatkan jatuh tempo lagi! Dapatkan pengingat otomatis untuk semua biaya tetap dan cicilanmu.</p>
            </div>
        </div>
    </div>
</section>

{{-- BUDGETING CERDAS DETAIL --}}
<section class="py-20 px-6 max-w-6xl mx-auto">
    <div class="grid md:grid-cols-2 gap-16 items-center">
        <div>
            <h2 class="text-3xl font-bold text-primary mb-4">Budgeting Cerdas</h2>
            <p class="text-gray-500 text-sm leading-relaxed mb-6">Sistem menghitung jatah belanja harianmu secara dinamis.</p>
            <ul class="space-y-3">
                <li class="flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    Update saldo otomatis setiap transaksi
                </li>
                <li class="flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    Penyesuaian batas harian secara real-time
                </li>
            </ul>
        </div>
        <div class="flex justify-center">
            <img src="{{asset('/preview-3.png')}}" alt="Budgeting Cerdas" class="w-64 drop-shadow-xl">
        </div>
    </div>
</section>

{{-- PENCATATAN CEPAT --}}
<section class="py-20 px-6 bg-gray-50">
    <div class="max-w-6xl mx-auto">
        <div class="grid md:grid-cols-2 gap-16 items-center">
            <div class="flex justify-center order-2 md:order-1">
                <img src="{{asset('/preview-2.png')}}" alt="Pencatatan Cepat" class="w-64 drop-shadow-xl">
            </div>
            <div class="order-1 md:order-2">
                <h2 class="text-3xl font-bold text-primary mb-4">Pencatatan Cepat (AI Voice & Scan)</h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">Malas mengetik? Cukup ucapkan transaksimu atau foto struk belanjamu. AI akan otomatis mengekstrak nominal, kategori, dan detail untukmu.</p>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl p-4 border border-gray-100">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-7 h-7 rounded-lg bg-secondary-light flex items-center justify-center">
                                <svg class="w-4 h-4 text-secondary" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a3 3 0 013 3v6a3 3 0 01-6 0V5a3 3 0 013-3zm-7 9a7 7 0 0014 0h-2a5 5 0 01-10 0H5zm7 9v-2a7 7 0 007-7h-2a5 5 0 01-10 0H5a7 7 0 007 7v2z"/></svg>
                            </div>
                            <span class="text-xs font-semibold text-gray-700">Voice</span>
                        </div>
                        <p class="text-xs text-gray-400">"Beli kopi 25 ribu"</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 border border-gray-100">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-7 h-7 rounded-lg bg-primary-light flex items-center justify-center">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <span class="text-xs font-semibold text-gray-700">Scan Struk</span>
                        </div>
                        <p class="text-xs text-gray-400">Deteksi item otomatis</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- PENGINGAT PINTAR --}}
<section class="py-20 px-6 max-w-6xl mx-auto">
    <div class="grid md:grid-cols-2 gap-16 items-center">
        <div>
            <h2 class="text-3xl font-bold text-primary mb-4">Pengingat Pintar</h2>
            <p class="text-gray-500 text-sm leading-relaxed">Jangan sampai kelewat tagihan. Pantau semua tagihan bulananmu dan atur deadline pembayaran dengan mudah melalui manajemen biaya tetap yang terorganisir.</p>
        </div>
        <div class="flex justify-center">
            <img src="{{asset('/preview-1.png')}}" alt="Pengingat Pintar" class="w-full max-w-sm drop-shadow-xl">
        </div>
    </div>
</section>

{{-- TESTIMONI --}}
<section id="testimoni" class="py-20 px-6 bg-gray-50">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-14">
            <h2 class="text-3xl font-bold text-gray-900 mb-3">Dipercaya Ribuan Orang</h2>
            <p class="text-gray-500 text-sm">Dengan cerita mereka yang telah berhasil bersama MOCO...</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-primary text-white rounded-2xl p-6">
                <div class="flex gap-1 mb-4">
                    @for ($i = 0; $i < 5; $i++)
                        <svg class="w-4 h-4 text-secondary" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    @endfor
                </div>
                <p class="text-sm leading-relaxed text-blue-100 mb-6">"MOCO mengubah cara saya melihat uang. Sebelumnya saya selalu bingung uang gaji ke mana. Sekarang semuanya tercatat rapi dan saya bisa nabung 30% dari penghasilan tiap bulan!"</p>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold">H</div>
                    <div>
                        <p class="font-semibold text-sm">Haechan</p>
                        <p class="text-xs text-blue-200">Marketing Director</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <div class="flex gap-1 mb-4">
                    @for ($i = 0; $i < 5; $i++)
                        <svg class="w-4 h-4 text-secondary" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    @endfor
                </div>
                <p class="text-sm leading-relaxed text-gray-600 mb-6">"Fitur scan struk-nya juara! Gak perlu ketik-ketik manual."</p>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-primary-light flex items-center justify-center text-xs font-bold text-primary">ML</div>
                    <div>
                        <p class="font-semibold text-sm text-gray-900">Mark Lee</p>
                        <p class="text-xs text-gray-400">Freelancer</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <div class="flex gap-1 mb-4">
                    @for ($i = 0; $i < 4; $i++)
                        <svg class="w-4 h-4 text-secondary" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    @endfor
                    <svg class="w-4 h-4 text-gray-200" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <p class="text-sm leading-relaxed text-gray-600 mb-6">"Sempat ragu pakai aplikasi keuangan, tapi fitur biaya tetap MOCO bikin tenang. Tagihan cicilan motor tidak pernah kelewat lagi!"</p>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-secondary-light flex items-center justify-center text-xs font-bold text-secondary">AN</div>
                    <div>
                        <p class="font-semibold text-sm text-gray-900">Chenle</p>
                        <p class="text-xs text-gray-400">Sales Muda</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA SECTION --}}
<section class="py-20 px-6">
    <div class="max-w-6xl mx-auto">
        <div class="bg-primary rounded-3xl px-8 py-16 text-center relative overflow-hidden">
            <div class="absolute top-4 left-1/2 -translate-x-1/2 text-4xl">🚀</div>
            <h2 class="text-3xl md:text-4xl font-bold text-white mt-8 mb-4">Siap Mengontrol Keuanganmu?</h2>
            <p class="text-blue-200 text-sm mb-8 max-w-md mx-auto">Bersama MOCO, raih kebebasan finansial tanpa ribet. Mulai atur keuanganmu dengan mudah!</p>
            <a href="https://play.google.com/store/apps/details?id=com.moco.moneycontrol&hl=en_US" class="inline-flex items-center gap-3 bg-secondary text-white font-semibold px-6 py-3 rounded-2xl hover:bg-secondary/90 transition-colors text-sm">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.76c.3.17.64.24.99.2l13.5-7.79-2.89-2.89L3.18 23.76zM.69 1.67C.27 2.1 0 2.78 0 3.67v16.67c0 .89.27 1.57.69 2l.11.1 9.34-9.34v-.22L.8 3.55.69 1.67zM20.23 9.67l-2.56-1.48-3.23 3.23 3.24 3.24 2.56-1.48c.73-.42.73-1.1 0-1.52l.01.01zM4.17.24L17.67 8.03l-2.89 2.89L3.18.23c.35-.04.7.03.99.01z"/></svg>
                Unduh di Google Play
            </a>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer class="bg-white border-t border-gray-100 py-8 px-6">
    <div class="max-w-6xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-primary font-bold text-lg">MOCO</span>
        </div>
        <p class="text-xs text-gray-400">© 2026 MOCO Financial Technologies. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
