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
4. Generate app key
   ```sh
    php artisan key:generate
    ```
5. Jalanin migration ke db
    ```sh
    php artisan migrate:fresh
    ```
6. Install package npm
    ```sh
    npm install
    ```
7. Build frontend
   ```sh
   npm run build
   ```
8. Jalanin app-nya
    ```sh
    composer run dev
    # halo butuh fitur notifikasi
    php artisan queue:work
    ```
> Buat file firebase key, taruh di `storage/app/private/firebase_key.json`

## API Documentation
**OpenAPI Spec**
This API documentation follows Open API standard and can be accessed from:
- Web UI: access at `/api/docs`.
- Raw JSON: access at `/docs/api.json`

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

### 2. Alur Kerja (Work-Flow)
Jangan asal nubruk! Ikuti urutan ini kalau dapet *task*:
1.  **Lihat Skeleton:** Gue udah nyiapin file-nya. Pahami class mana yang harus diisi.
2.  **Pahami Codebase:** BACA KODE YANG SUDAH ADA. Kalau sudah ada *Service* yang bisa dipake (misal: pengelolaan uang di `Money` Value Object / Service), pake itu! *Don't reinvent the wheel!*
3.  **FormRequest & Strict DTO:** Validasi input wajib di *Form Request*. Jangan pakai metode array mentah! Bikin method `toDTO()` di dalam *Request* untuk *mapping* data yang tervalidasi langsung ke *constructor* DTO.
4.  **Action:** Taruh logika bisnis utama di sini. Cukup satu *public method* yaitu `execute()`.
5.  **Resource:** Data dari Model/Action wajib dibungkus ke `JsonResource` buat nyesuain *format* JSON (misal ubah *snake_case* jadi *camelCase*) dan menangani relasi sebelum dikirim ke *frontend*.
6.  **Controller:** Cukup otorisasi (Gate), panggil `toDTO()`, eksekusi Action, bungkus hasil ke Resource, dan kembalikan pake metode wrapper `$this->successResponse()`.

### 3. Dilarang Keras (Daftar Merah PR Rejected)
Gue bakal REJECT otomatis PR lu kalau ketemu hal ini:
*   **Reinvent the Wheel:** Nulis logika *sum* atau kalkulasi rumit yang sebenernya udah ada di *Service* atau *Value Object* bawaan.
*   **Logic di Controller:** Ada `Model::create()` atau `foreach` ribet di Controller? Langsung gue hapus.
*   **Bahasa Indonesia di Kode:** Ada variabel atau komen bahasa Indo di kodingan.
*   **Float buat Duit:** Haram pake *float* atau *integer* telanjang buat uang. Cast semua nominal ke *string* di DTO dan kalkulasi pakai *Value Object* uang yang ada.
*   **Looping Query PHP:** Narik data pake `->get()` cuma buat di-hitung totalnya di PHP. Pake `->sum()` atau `->count()` langsung di query database.
*   **Hardcoded Status:** Pake `'pending'` atau `'paid'` secara manual. Gunakan `Enums` yang sudah disediakan. Kalau emang butuh *enum* baru, diskusiin dulu alasannya.
*   **Ngeludahin Raw Model ke API:** Ngereturn *raw* object Eloquent langsung dari Controller ke *frontend*. **Wajib dibungkus pakai `JsonResource`!**

---

### Contoh Pola yang Bener (Role Model)

Controller lu cuma bertugas jadi 'satpam' (otorisasi) dan 'kurir' (oper data). Nggak boleh ada logika di sini!

**1. Di dalam Form Request (Urusan Validasi & DTO Mapping):**
```php
public function toDTO(): CreateTransactionData
{
    // Mapping manual & type-safe ke constructor DTO
    return new CreateTransactionData(
        amount: $this->validated('amount'),
        name: $this->validated('name'),
        type: TransactionType::from($this->validated('type')),
        categoryId: $this->validated('categoryId'),
    );
}
```

**2. Di dalam Controller (Urusan Alur & Respon):**

```php
public function store(StoreTransactionRequest $request, CreateTransactionAction $action): ApiResponse
{
    // 1. Satpam: Otorisasi via Gate
    Gate::authorize('create', Transaction::class);

    // 2. Data mateng langsung diconvert ke DTO yang strict
    $dto = $request->toDTO();

    // 3. Logic berat & urusan database dijalankan Action
    $result = $action->execute(Auth::user(), $dto);

    // 4. Respon seragam: Bungkus ke Resource, lalu lempar ke ApiResponse Wrapper
    return $this->successResponse(
        data: new TransactionResource($result),
        message: 'Transaction created successfully.',
        status: 201
    );
}
```
