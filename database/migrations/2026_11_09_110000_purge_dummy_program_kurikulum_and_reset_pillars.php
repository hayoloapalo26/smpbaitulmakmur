<?php

use App\Console\Commands\ResetLaunchDataCommand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membersihkan seluruh data dummy program kerja (termasuk dummy kurikulum, kesiswaan, sarpras, humas, bk)
     * dan memastikan data siap launching resmi murni untuk diinput oleh personil sekolah.
     */
    public function up(): void
    {
        if (Schema::hasTable('institutions') && \App\Models\Institution::count() === 0) {
            return;
        }

        // 1. Bersihkan sisa program kurikulum dummy spesifik
        if (Schema::hasTable('program_kurikulums')) {
            DB::table('program_kurikulums')
                ->where('nama_program', 'LIKE', '%Pengembangan Modul Ajar%')
                ->orWhere('nama_program', 'LIKE', '%Dummy%')
                ->delete();
        }

        // 2. Bersihkan sisa school pillar tasks dummy
        if (Schema::hasTable('school_pillar_tasks')) {
            DB::table('school_pillar_tasks')->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
