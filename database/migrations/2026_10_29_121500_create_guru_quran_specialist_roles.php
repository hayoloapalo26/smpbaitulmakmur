<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Subject;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat 4 Role Guru Al-Qur'an Spesialis
        $roles = [
            'guru_quran_yanbua',
            'guru_quran_tahsin',
            'guru_quran_tahfidz_dasar',
            'guru_quran_tahfidz_lanjutan',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Pastikan Mata Pelajaran Spesialis Al-Qur'an tersedia agar Waka Kurikulum mudah melakukan routing
        $specialistSubjects = [
            ['name' => 'Al-Qur\'an (Yanbu\'a)', 'kode' => 'QUR_YANBUA'],
            ['name' => 'Al-Qur\'an (Tahsin)', 'kode' => 'QUR_TAHSIN'],
            ['name' => 'Al-Qur\'an (Tahfidz Dasar)', 'kode' => 'QUR_TFZ_DASAR'],
            ['name' => 'Al-Qur\'an (Tahfidz Lanjutan)', 'kode' => 'QUR_TFZ_LANJUTAN'],
        ];

        foreach ($specialistSubjects as $sub) {
            $existing = Subject::withoutGlobalScopes()
                ->where('name', $sub['name'])
                ->orWhere('kode', $sub['kode'])
                ->first();

            if ($existing) {
                $existing->update([
                    'name' => $sub['name'],
                    'kode' => $sub['kode'],
                ]);
            } else {
                Subject::withoutGlobalScopes()->create([
                    'name' => $sub['name'],
                    'kode' => $sub['kode'],
                ]);
            }
        }

        // 3. Mapping Otomatis ke User Guru Al-Qur'an yang sudah ada (jika cocok)
        // User Zaenal Musthofa -> Yanbu'a
        $guruYanbua = User::where('email', 'zaenal.musthofa@smpi.com')->first();
        if ($guruYanbua && !$guruYanbua->hasRole('guru_quran_yanbua')) {
            $guruYanbua->assignRole('guru_quran_yanbua');
        }

        // User Chisbulloh -> Tahsin
        $guruTahsin = User::where('email', 'chisbulloh@smpi.com')->first();
        if ($guruTahsin && !$guruTahsin->hasRole('guru_quran_tahsin')) {
            $guruTahsin->assignRole('guru_quran_tahsin');
        }

        // User M. Romli -> Tahfidz Dasar
        $guruTfzDasar = User::where('email', 'm.romli@smpi.com')->first();
        if ($guruTfzDasar && !$guruTfzDasar->hasRole('guru_quran_tahfidz_dasar')) {
            $guruTfzDasar->assignRole('guru_quran_tahfidz_dasar');
        }

        // User Lukman Hakim / email alquran@gmail.com -> Tahfidz Lanjutan
        $guruTfzLanjutan = User::where('email', 'alquran@gmail.com')->first();
        if ($guruTfzLanjutan && !$guruTfzLanjutan->hasRole('guru_quran_tahfidz_lanjutan')) {
            $guruTfzLanjutan->assignRole('guru_quran_tahfidz_lanjutan');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tetap dipertahankan agar tidak menghapus konfigurasi role yang aktif
    }
};
