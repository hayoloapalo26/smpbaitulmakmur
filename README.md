# Sistem Informasi SMPI - Yayasan Baitul Makmur

Modul SMPI (Sekolah Menengah Pertama Islam) untuk Yayasan Baitul Makmur.
Dibangun menggunakan Laravel 12, Filament v5, Livewire 4, dan Spatie Permissions.

## Cara Instalasi Lokal

1. **Clone/Download Repository**
2. **Install Dependencies PHP & Node**
   ```bash
   composer install
   npm install
   ```
3. **Setup Environment Variables**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Pastikan Anda telah membuat database MySQL bernama `smpi_db` dan mengatur kredensialnya di file `.env`.*
4. **Migrasi Database & Seeding**
   ```bash
   php artisan migrate:fresh --seed
   ```
   *Seeder akan membuat akun dummy untuk setiap role (contoh: `kepala-sekolah@smpi-bbm.test` dengan password `password`), serta data dummy kelas, siswa, mata pelajaran, dll.*
5. **Build Assets**
   ```bash
   npm run build
   ```
6. **Jalankan Aplikasi**
   ```bash
   php artisan serve
   ```

Akses portal staff di: `http://localhost:8000/staff` (Panel Filament)
Akses portal siswa/orang tua di: `http://localhost:8000/portal` (Portal Livewire)
