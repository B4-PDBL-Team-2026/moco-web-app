# A Monorepo of MOCO (RESTful API + Web App)

## Getting Started
Cara install dan run app:
1. Clone repo-nya
2. Install dependencies
    ```sh
    composer install
    ```
3. Konfigurasi .env file
   ```sh
    cp .env.example .env
    ```
5. Generate app key
   ```sh
    php artisan key:generate
    ```
7. Jalanin migration ke db
    ```sh
    php artisan migrate:fresh
    ```
9. Install package npm
    ```sh
    npm install
    ```
11. Jalanin app-nya
    ```sh
    composer run dev
    ```

## Documentation
**OpenAPI Spec**

API documentation is accessible from path '/api/docs'. You have to run the app first to access it.

## Developer Notes
Standar Koding & Arsitektur
Dokumen ini adalah aturan main wajib buat siapa pun yang mau push kode ke repositori ini. Gue udah buatin Skeleton Files, tugas lu cuma ngisi isinya, tapi kalo ada yang belum tercover sama skeleton files, bikin aja gapapa yang penting tujuan jelas dan sesuai clean code principles.

1. Aturan Emas: Bahasa Inggris & Penamaan
English Only in Code: Nama variable, method, class, dan KOMENTAR wajib pake Bahasa Inggris. Nggak boleh ada komen di kode // hitung total saldo atau $variabelKosong. Pake // calculate total balance atau $emptyVariable.
Pengecualian:
    - Isi data di database (seeder/metadata) boleh Bahasa Indonesia (misal: name => 'Bayar Kosan').
Contoh:
    - Clean Naming: * Action = Kata Kerja (Verb). Contoh: CreateTransactionAction.
    - Service/Calculator = Kata Benda (Noun). Contoh: AllowanceCalculator, bukan CalculateService.
    - Boolean = Pake prefix is, has, atau should. Contoh: hasOnboarded.

2. Alur Kerja (Work-Flow)
Jangan asal nubruk! Ikuti urutan ini kalau dapet task:
    - Lihat Skeleton: Gue udah nyiapin file-nya. Pahami class mana yang harus diisi.
    - Pahami Codebase: BACA KODE YANG SUDAH ADA. Kalau sudah ada Service yang bisa dipake (misal: MoneyService), pake itu! Jangan tulis ulang logikanya di Action atau Controller. Don't reinvent the wheel!
    - FormRequest & DTO: Validasi input di Request, lalu bungkus ke DTO. Jangan lempar array mentah ke Action.
    - Action: Taruh logika utama di sini. Cukup satu public method yaitu execute().
    - Controller: Cukup panggil Action dan balikin respon pake ApiResponse trait.

3. Dilarang Keras (Daftar Merah PR Rejected)
Gue bakal REJECT otomatis PR lu kalau ketemu hal ini:
    - Reinvent the Wheel: Nulis logika sum atau kalkulasi yang sebenernya udah ada di Service.
    - Logic di Controller: Ada Model::create() atau foreach ribet di Controller? Langsung gue hapus.
    - Bahasa Indonesia: Ada variabel atau komen bahasa Indo di kodingan.
    - Float buat Duit: Haram pake float buat uang. Wajib pake MoneyService, cast semua ke string di DTO.
    - Looping Query: Narik data pake ->get() cuma buat di-hitung totalnya di PHP. Pake ->sum() atau ->count() langsung di query database.
    - Hardcoded Status: Pake 'pending' atau 'paid' secara manual. Gunakan Enums yang sudah disediakan, dan jangan bikin lagi, gue udah bikin semua enum yang dibutuhin, kalo emang bener-bener ada kebutuhan enum baru dan alasan bisa diterima, okelah gue setuju.

Contoh pola yang bener:
```php 
// Controller cuma jadi 'satpam' dan 'kurir'
public function store(StoreOnboardingRequest $request, CompleteOnboardingAction $action)
{
    // 1. Data mateng masuk ke DTO
    $dto = CompleteOnboardingData::fromData($request->validated());

    // 2. Logic berat dijalankan Action
    $result = $action->execute(auth()->id(), $dto);

    // 3. Respon seragam
    return $this->success($result, 'Onboarding completed.');
}
```
