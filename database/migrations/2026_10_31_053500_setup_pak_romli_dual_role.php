<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pastikan role spesialis Al-Qur'an dan Yayasan tersedia
        $rolesToEnsure = [
            'ketua_yayasan',
            'guru_alquran',
            'guru_quran_tahfidz_lanjutan',
        ];

        foreach ($rolesToEnsure as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Temukan user Pak Romli (Ustadz Romli)
        $romli = User::withoutGlobalScopes()
            ->where('username', 'pakromli')
            ->orWhere('email', 'romli@gmail.com')
            ->orWhere('email', 'm.romli@smpi.com')
            ->orWhere('name', 'like', '%Romli%')
            ->first();

        if ($romli) {
            // Tetapkan institution_id = 1 (Yayasan Baitul Makmur) sebagai pimpinan Yayasan
            $romli->institution_id = 1;
            $romli->is_active = true;
            $romli->save();

            // Pasang role ganda: Ketua Yayasan DAN Guru Al-Qur'an Tahfidz Lanjutan
            if (!$romli->hasRole('ketua_yayasan')) {
                $romli->assignRole('ketua_yayasan');
            }
            if (!$romli->hasRole('guru_quran_tahfidz_lanjutan')) {
                $romli->assignRole('guru_quran_tahfidz_lanjutan');
            }
            if (!$romli->hasRole('guru_alquran')) {
                $romli->assignRole('guru_alquran');
            }
        }
    }

    public function down(): void
    {
        // No destructive reversal to protect production state
    }
};
