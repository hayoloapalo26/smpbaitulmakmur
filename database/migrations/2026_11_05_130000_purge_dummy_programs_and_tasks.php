<?php

use App\Console\Commands\ResetLaunchDataCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membersihkan seluruh data dummy program kerja (Kurikulum, Kesiswaan, Sarpras, Humas, BK, KS)
     * dan memastikan data siap launching secara pure untuk diinput oleh personil bersangkutan.
     * Data master siswa, guru, yayasan, jabatan/roles, dan kelas 100% tetap aman.
     */
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('institutions') && \App\Models\Institution::count() === 0) {
            return;
        }

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
