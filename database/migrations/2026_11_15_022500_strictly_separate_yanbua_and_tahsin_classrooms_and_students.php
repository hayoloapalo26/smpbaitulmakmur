<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $institutions = Institution::withoutGlobalScopes()->where('id', '!=', 1)->get();
        if ($institutions->isEmpty()) {
            // Jika belum ada institusi (misal saat fresh migration / test environment), skip pembuatan data rombel
            return;
        }

        foreach ($institutions as $inst) {
            $instId = $inst->id;

            // 1. Dapatkan guru spesialis Al-Qur'an
            $guruYanbua = User::withoutGlobalScopes()
                ->where(function ($q) use ($instId) {
                    $q->where('institution_id', $instId)->orWhere('institution_id', 1)->orWhereNull('institution_id');
                })
                ->where(function ($q) {
                    $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['guru_quran_yanbua', 'guru_yanbua']))
                      ->orWhere('email', 'like', '%zaenal%');
                })
                ->first();

            $guruTahsin = User::withoutGlobalScopes()
                ->where(function ($q) use ($instId) {
                    $q->where('institution_id', $instId)->orWhere('institution_id', 1)->orWhereNull('institution_id');
                })
                ->where(function ($q) {
                    $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['guru_quran_tahsin', 'guru_tahsin']))
                      ->orWhere('email', 'like', '%chisbulloh%');
                })
                ->first();

            $guruTfzDasar = User::withoutGlobalScopes()
                ->where(function ($q) use ($instId) {
                    $q->where('institution_id', $instId)->orWhere('institution_id', 1)->orWhereNull('institution_id');
                })
                ->where(function ($q) {
                    $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['guru_quran_tahfidz_dasar', 'guru_tahfidz_dasar']))
                      ->orWhere('email', 'like', '%romli%');
                })
                ->first();

            $guruTfzLanjutan = User::withoutGlobalScopes()
                ->where(function ($q) use ($instId) {
                    $q->where('institution_id', $instId)->orWhere('institution_id', 1)->orWhereNull('institution_id');
                })
                ->where(function ($q) {
                    $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['guru_quran_tahfidz_lanjutan', 'guru_tahfidz_lanjutan']))
                      ->orWhere('email', 'like', '%alquran%');
                })
                ->first();

            // 2. Cek apakah ada rombel gabungan "Tahsin Yanbu'a" atau sejenisnya
            $combinedClasses = Classroom::withoutGlobalScopes()
                ->where('institution_id', $instId)
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('name', 'like', '%yanbu%')->where('name', 'like', '%tahsin%');
                    })->orWhere(function ($sub) {
                        $sub->where('tingkat', 'like', '%yanbu%')->where('tingkat', 'like', '%tahsin%');
                    });
                })
                ->get();

            foreach ($combinedClasses as $comb) {
                // Ubah rombel gabungan ini menjadi Rombel Yanbu'a murni
                $comb->update([
                    'name' => 'Yanbu\'a',
                    'tingkat' => 'Yanbu\'a SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $guruYanbua?->id ?? $comb->wali_kelas_id,
                ]);
            }

            // 3. Pastikan Rombel Yanbu'a Mandiri ADA
            $clsYanbua = Classroom::withoutGlobalScopes()
                ->where('institution_id', $instId)
                ->where(function ($q) {
                    $q->where('name', 'Yanbu\'a')
                      ->orWhere(function ($sub) {
                          $sub->where('name', 'like', '%yanbu%')
                              ->where('name', 'not like', '%tahsin%');
                      });
                })
                ->first();

            if (!$clsYanbua) {
                $clsYanbua = Classroom::withoutGlobalScopes()->create([
                    'institution_id' => $instId,
                    'name' => 'Yanbu\'a',
                    'tingkat' => 'Yanbu\'a SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $guruYanbua?->id,
                ]);
            } else {
                $clsYanbua->update([
                    'name' => 'Yanbu\'a',
                    'tingkat' => 'Yanbu\'a SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $clsYanbua->wali_kelas_id ?: $guruYanbua?->id,
                ]);
            }

            // 4. Pastikan Rombel Tahsin Mandiri ADA (TERPISAH DARI YANBU'A)
            $clsTahsin = Classroom::withoutGlobalScopes()
                ->where('institution_id', $instId)
                ->where(function ($q) {
                    $q->where('name', 'Tahsin')
                      ->orWhere(function ($sub) {
                          $sub->where('name', 'like', '%tahsin%')
                              ->where('name', 'not like', '%yanbu%');
                      });
                })
                ->first();

            if (!$clsTahsin) {
                $clsTahsin = Classroom::withoutGlobalScopes()->create([
                    'institution_id' => $instId,
                    'name' => 'Tahsin',
                    'tingkat' => 'Tahsin SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $guruTahsin?->id,
                ]);
            } else {
                $clsTahsin->update([
                    'name' => 'Tahsin',
                    'tingkat' => 'Tahsin SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $clsTahsin->wali_kelas_id ?: $guruTahsin?->id,
                ]);
            }

            // 5. Pastikan Rombel Tahfidz Dasar Mandiri ADA
            $clsTfzDasar = Classroom::withoutGlobalScopes()
                ->where('institution_id', $instId)
                ->where('name', 'like', '%dasar%')
                ->first();

            if (!$clsTfzDasar) {
                Classroom::withoutGlobalScopes()->create([
                    'institution_id' => $instId,
                    'name' => 'Tahfidz Dasar',
                    'tingkat' => 'Tahfidz Dasar SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $guruTfzDasar?->id,
                ]);
            }

            // 6. Pastikan Rombel Tahfidz Lanjutan Mandiri ADA
            $clsTfzLanjutan = Classroom::withoutGlobalScopes()
                ->where('institution_id', $instId)
                ->where('name', 'like', '%lanjutan%')
                ->first();

            if (!$clsTfzLanjutan) {
                Classroom::withoutGlobalScopes()->create([
                    'institution_id' => $instId,
                    'name' => 'Tahfidz Lanjutan',
                    'tingkat' => 'Tahfidz Lanjutan SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $guruTfzLanjutan?->id,
                ]);
            }

            // 7. Bersihkan data penempatan siswa yang mungkin tercampur
            if (\Illuminate\Support\Facades\Schema::hasColumn('students', 'kelas_quran')) {
                // Siswa di rombel Yanbu'a dipastikan berstatus kelas_quran = 'yanbua'
                Student::withoutGlobalScopes()
                    ->where('classroom_id', $clsYanbua->id)
                    ->where(function ($q) {
                        $q->whereNull('kelas_quran')->orWhere('kelas_quran', '');
                    })
                    ->update([
                        'kelas_quran' => 'yanbua',
                        'guru_quran_id' => $guruYanbua?->id,
                    ]);

                // Siswa di rombel Tahsin dipastikan berstatus kelas_quran = 'tahsin'
                Student::withoutGlobalScopes()
                    ->where('classroom_id', $clsTahsin->id)
                    ->where(function ($q) {
                        $q->whereNull('kelas_quran')->orWhere('kelas_quran', '');
                    })
                    ->update([
                        'kelas_quran' => 'tahsin',
                        'guru_quran_id' => $guruTahsin?->id,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Biarkan data tetap terpisah rapi
    }
};
