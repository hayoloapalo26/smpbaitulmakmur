<?php

use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\TujuanPembelajaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom kode_cp ke tabel capaian_pembelajarans jika belum ada
        if (Schema::hasTable('capaian_pembelajarans') && !Schema::hasColumn('capaian_pembelajarans', 'kode_cp')) {
            Schema::table('capaian_pembelajarans', function (Blueprint $table) {
                $table->string('kode_cp')->nullable()->after('subject_id');
            });
        }

        // 2. Backfill kode_cp untuk semua data CapaianPembelajaran yang ada (1, 2, 3...)
        if (Schema::hasTable('capaian_pembelajarans')) {
            $cpsByGroup = CapaianPembelajaran::orderBy('id', 'asc')->get()->groupBy(function ($cp) {
                return "{$cp->guru_id}_{$cp->subject_id}_{$cp->tingkat}";
            });

            foreach ($cpsByGroup as $group => $cps) {
                $index = 1;
                foreach ($cps as $cp) {
                    if (empty($cp->kode_cp)) {
                        $cp->update(['kode_cp' => (string) $index]);
                    }
                    $index++;
                }
            }
        }

        // 3. Backfill kode_tp untuk semua data TujuanPembelajaran yang ada (1.1, 1.2, ..., 2.1, dst.)
        if (Schema::hasTable('tujuan_pembelajarans')) {
            $tpsByCp = TujuanPembelajaran::with('capaianPembelajaran')->orderBy('id', 'asc')->get()->groupBy('capaian_pembelajaran_id');

            foreach ($tpsByCp as $cpId => $tps) {
                $cp = CapaianPembelajaran::find($cpId);
                $cpKode = $cp?->kode_cp ?: '1';
                $tpIndex = 1;

                foreach ($tps as $tp) {
                    // Update jika kode masih berformat default lama TP.* atau kosong
                    if (empty($tp->kode_tp) || str_starts_with($tp->kode_tp, 'TP.')) {
                        $tp->update(['kode_tp' => "{$cpKode}.{$tpIndex}"]);
                    }
                    $tpIndex++;
                }
            }
        }

        // 4. Sinkronisasikan ulang seluruh ATP
        if (Schema::hasTable('alur_tujuan_pembelajarans')) {
            $atps = AlurTujuanPembelajaran::all();
            foreach ($atps as $atp) {
                if ($atp->guru_id && $atp->subject_id) {
                    AlurTujuanPembelajaran::syncForTeacherAndSubject(
                        $atp->guru_id,
                        $atp->subject_id,
                        $atp->tingkat,
                        $atp->tahun_ajaran,
                        $atp->semester
                    );
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('capaian_pembelajarans') && Schema::hasColumn('capaian_pembelajarans', 'kode_cp')) {
            Schema::table('capaian_pembelajarans', function (Blueprint $table) {
                $table->dropColumn('kode_cp');
            });
        }
    }
};
