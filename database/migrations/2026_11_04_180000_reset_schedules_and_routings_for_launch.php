<?php

use App\Console\Commands\ResetLaunchDataCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Secara otomatis mereset jadwal pelajaran, routing guru mapel, dan progress pilar
     * untuk persiapan launching resmi saat user menjalankan `php artisan migrate --force`.
     * Data siswa, guru, yayasan, jabatan/roles, kelas, dan master mapel 100% tetap aman.
     */
    public function up(): void
    {
        ResetLaunchDataCommand::executeReset();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
