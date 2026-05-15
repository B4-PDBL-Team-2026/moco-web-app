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
