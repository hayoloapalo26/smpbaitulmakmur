<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Classroom;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('classrooms')) {
            Schema::table('classrooms', function (Blueprint $table) {
                if (!Schema::hasColumn('classrooms', 'tipe')) {
                    $table->string('tipe', 30)->default('reguler')->after('tingkat');
                }
            });

            // 1. Klasifikasi otomatis kelas Al-Qur'an vs Kelas Reguler
            $allClassrooms = Classroom::withoutGlobalScopes()->get();
            foreach ($allClassrooms as $cls) {
                $haystack = strtolower(($cls->name ?? '') . ' ' . ($cls->tingkat ?? ''));
                $isQuran = str_contains($haystack, 'tahsin')
                    || str_contains($haystack, 'yanbu')
                    || str_contains($haystack, 'tahfidz')
                    || str_contains($haystack, 'quran')
                    || str_contains($haystack, 'halaqah');

                $newTipe = $isQuran ? 'quran' : 'reguler';
                DB::table('classrooms')->where('id', $cls->id)->update(['tipe' => $newTipe]);
            }

            // 2. Koreksi role: Guru Al-Qur'an bukan Wali Kelas!
            // Pastikan role dasar Al-Qur'an tersedia
            Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'guru_quran_yanbua', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'guru_quran_tahfidz_dasar', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'guru_quran_tahfidz_lanjutan', 'guard_name' => 'web']);

            $quranClasses = Classroom::withoutGlobalScopes()->where('tipe', 'quran')->whereNotNull('wali_kelas_id')->get();
            foreach ($quranClasses as $qc) {
                $teacher = User::withoutGlobalScopes()->find($qc->wali_kelas_id);
                if (!$teacher) continue;

                // Cek apakah guru ini juga memegang kelas reguler
                $hasRegulerClass = Classroom::withoutGlobalScopes()
                    ->where('tipe', 'reguler')
                    ->where('wali_kelas_id', $teacher->id)
                    ->exists();

                // Jika guru Al-Qur'an murni (tidak memegang kelas reguler), cabut role wali_kelas
                if (!$hasRegulerClass && $teacher->hasRole('wali_kelas')) {
                    $teacher->removeRole('wali_kelas');
                }

                // Berikan role spesialis Al-Qur'an yang sesuai
                $classNameLower = strtolower($qc->name . ' ' . $qc->tingkat);
                if (str_contains($classNameLower, 'tahsin')) {
                    if (!$teacher->hasRole('guru_quran_tahsin')) $teacher->assignRole('guru_quran_tahsin');
                } elseif (str_contains($classNameLower, 'yanbu')) {
                    if (!$teacher->hasRole('guru_quran_yanbua')) $teacher->assignRole('guru_quran_yanbua');
                } elseif (str_contains($classNameLower, 'dasar')) {
                    if (!$teacher->hasRole('guru_quran_tahfidz_dasar')) $teacher->assignRole('guru_quran_tahfidz_dasar');
                } elseif (str_contains($classNameLower, 'lanjutan')) {
                    if (!$teacher->hasRole('guru_quran_tahfidz_lanjutan')) $teacher->assignRole('guru_quran_tahfidz_lanjutan');
                }

                if (!$teacher->isQuranTeacher()) {
                    $teacher->assignRole('guru_alquran');
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('classrooms') && Schema::hasColumn('classrooms', 'tipe')) {
            Schema::table('classrooms', function (Blueprint $table) {
                $table->dropColumn('tipe');
            });
        }
    }
};
