<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\User;
use App\Models\Subject;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pastikan institution SMP (id = 2) dan Yayasan (id = 1) terakomodir jika sudah ada
        $institutions = Institution::withoutGlobalScopes()->where('id', '!=', 1)->get();
        if ($institutions->isEmpty()) {
            // Jika belum ada institusi (misal saat fresh migration di test suite), lewati seeding
            return;
        }

        foreach ($institutions as $inst) {
            $instId = $inst->id;

            // Cari guru spesialis untuk mapping default jika tersedia
            $guruYanbua = User::withoutGlobalScopes()
                ->where(function ($q) use ($instId) {
                    $q->where('institution_id', $instId)->orWhere('institution_id', 1)->orWhereNull('institution_id');
                })
                ->whereHas('roles', fn ($r) => $r->where('name', 'guru_quran_yanbua'))
                ->first();

            $guruTahsin = User::withoutGlobalScopes()
                ->where(function ($q) use ($instId) {
                    $q->where('institution_id', $instId)->orWhere('institution_id', 1)->orWhereNull('institution_id');
                })
                ->whereHas('roles', fn ($r) => $r->where('name', 'guru_quran_tahsin'))
                ->first();

            $guruTfzDasar = User::withoutGlobalScopes()
                ->where(function ($q) use ($instId) {
                    $q->where('institution_id', $instId)->orWhere('institution_id', 1)->orWhereNull('institution_id');
                })
                ->whereHas('roles', fn ($r) => $r->where('name', 'guru_quran_tahfidz_dasar'))
                ->first();

            $guruTfzLanjutan = User::withoutGlobalScopes()
                ->where(function ($q) use ($instId) {
                    $q->where('institution_id', $instId)->orWhere('institution_id', 1)->orWhereNull('institution_id');
                })
                ->whereHas('roles', fn ($r) => $r->where('name', 'guru_quran_tahfidz_lanjutan'))
                ->first();

            // 1. Rombel Yanbu'a
            $clsYanbua = Classroom::withoutGlobalScopes()
                ->where('institution_id', $instId)
                ->where(function ($q) {
                    $q->where('name', 'like', '%yanbu%')->orWhere('tingkat', 'like', '%yanbu%');
                })
                ->first();

            if (!$clsYanbua) {
                Classroom::withoutGlobalScopes()->create([
                    'institution_id' => $instId,
                    'name' => 'Yanbu\'a',
                    'tingkat' => 'Yanbu\'a SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $guruYanbua?->id,
                ]);
            } else {
                $clsYanbua->update([
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $clsYanbua->wali_kelas_id ?: $guruYanbua?->id,
                ]);
            }

            // 2. Rombel Tahsin
            $clsTahsin = Classroom::withoutGlobalScopes()
                ->where('institution_id', $instId)
                ->where(function ($q) {
                    $q->where('name', 'like', '%tahsin%')->orWhere('tingkat', 'like', '%tahsin%');
                })
                ->first();

            if (!$clsTahsin) {
                Classroom::withoutGlobalScopes()->create([
                    'institution_id' => $instId,
                    'name' => 'Tahsin',
                    'tingkat' => 'Tahsin SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $guruTahsin?->id,
                ]);
            } else {
                $clsTahsin->update([
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $clsTahsin->wali_kelas_id ?: $guruTahsin?->id,
                ]);
            }

            // 3. Rombel Tahfidz Dasar
            $clsTfzDasar = Classroom::withoutGlobalScopes()
                ->where('institution_id', $instId)
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('name', 'like', '%dasar%')
                            ->orWhere('tingkat', 'like', '%dasar%');
                    });
                })
                ->first();

            if (!$clsTfzDasar) {
                Classroom::withoutGlobalScopes()->create([
                    'institution_id' => $instId,
                    'name' => 'Tahfidz Dasar',
                    'tingkat' => 'Tahfidz Dasar SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $guruTfzDasar?->id,
                ]);
            } else {
                $clsTfzDasar->update([
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $clsTfzDasar->wali_kelas_id ?: $guruTfzDasar?->id,
                ]);
            }

            // 4. Rombel Tahfidz Lanjutan
            $clsTfzLanjutan = Classroom::withoutGlobalScopes()
                ->where('institution_id', $instId)
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('name', 'like', '%lanjutan%')
                            ->orWhere('tingkat', 'like', '%lanjutan%');
                    });
                })
                ->first();

            if (!$clsTfzLanjutan) {
                Classroom::withoutGlobalScopes()->create([
                    'institution_id' => $instId,
                    'name' => 'Tahfidz Lanjutan',
                    'tingkat' => 'Tahfidz Lanjutan SMP',
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $guruTfzLanjutan?->id,
                ]);
            } else {
                $clsTfzLanjutan->update([
                    'tipe' => Classroom::TIPE_QURAN,
                    'wali_kelas_id' => $clsTfzLanjutan->wali_kelas_id ?: $guruTfzLanjutan?->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Biarkan data tetap ada
    }
};
