<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roles = [
            'guru_mapel',
            'guru_kelas',
            'wali_kelas',
            'guru_alquran',
            'guru_quran_yanbua',
            'guru_quran_tahsin',
            'guru_quran_tahfidz_dasar',
            'guru_quran_tahfidz_lanjutan',
            'guru_yanbua',
            'guru_tahsin',
            'guru_tahfidz',
            'guru_tahfidz_dasar',
            'guru_tahfidz_lanjutan',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tetap pertahankan role demi integritas data dan relasi user
    }
};
