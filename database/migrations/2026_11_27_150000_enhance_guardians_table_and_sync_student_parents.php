<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            if (!Schema::hasColumn('guardians', 'institution_id')) {
                $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
            }
        });

        // Sinkronisasi data orang tua dari tabel students ke guardians & student_guardians
        try {
            $students = DB::table('students')->get();
            foreach ($students as $s) {
                // Cek apakah siswa ini sudah memiliki kaitan di student_guardians
                $exists = DB::table('student_guardians')->where('student_id', $s->id)->exists();
                if ($exists) {
                    continue;
                }

                $parentName = null;
                $relation = 'Ayah';
                $phone = '-';

                if (!empty($s->nama_ayah) && trim($s->nama_ayah) !== '') {
                    $parentName = trim($s->nama_ayah);
                    $relation = 'Ayah';
                    $phone = !empty($s->no_hp_ayah) ? trim($s->no_hp_ayah) : (!empty($s->no_telepon_orang_tua) ? trim($s->no_telepon_orang_tua) : '-');
                } elseif (!empty($s->nama_ibu) && trim($s->nama_ibu) !== '') {
                    $parentName = trim($s->nama_ibu);
                    $relation = 'Ibu';
                    $phone = !empty($s->no_hp_ibu) ? trim($s->no_hp_ibu) : (!empty($s->no_telepon_orang_tua) ? trim($s->no_telepon_orang_tua) : '-');
                } elseif (!empty($s->nama_wali) && trim($s->nama_wali) !== '') {
                    $parentName = trim($s->nama_wali);
                    $relation = 'Wali';
                    $phone = !empty($s->no_hp_wali) ? trim($s->no_hp_wali) : (!empty($s->no_telepon_orang_tua) ? trim($s->no_telepon_orang_tua) : '-');
                }

                if (!$parentName) {
                    continue;
                }

                $instId = $s->institution_id ?: 2;

                // Cari guardian yang sama berdasarkan nama dan institusi agar saudara kandung otomatis digabungkan
                $guardian = DB::table('guardians')
                    ->where('name', $parentName)
                    ->where('institution_id', $instId)
                    ->first();

                if (!$guardian) {
                    $guardianId = DB::table('guardians')->insertGetId([
                        'institution_id' => $instId,
                        'name'           => $parentName,
                        'phone'          => $phone,
                        'address'        => $s->alamat ?? '-',
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                } else {
                    $guardianId = $guardian->id;
                    if (($guardian->phone === '-' || empty($guardian->phone)) && $phone !== '-') {
                        DB::table('guardians')->where('id', $guardianId)->update(['phone' => $phone]);
                    }
                }

                // Tautkan di student_guardians
                DB::table('student_guardians')->insert([
                    'student_id'  => $s->id,
                    'guardian_id' => $guardianId,
                    'relation'    => $relation,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Lanjutkan jika ada kendala non-fatal pada data seeder
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            if (Schema::hasColumn('guardians', 'institution_id')) {
                $table->dropForeign(['institution_id']);
                $table->dropColumn('institution_id');
            }
        });
    }
};
