<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom fases di capaian_pembelajarans (multiple choice fase)
        if (Schema::hasTable('capaian_pembelajarans')) {
            Schema::table('capaian_pembelajarans', function (Blueprint $table) {
                if (!Schema::hasColumn('capaian_pembelajarans', 'fases')) {
                    $table->json('fases')->nullable()->after('fase');
                }
            });

            // Backfill data lama jika ada
            $existingCps = DB::table('capaian_pembelajarans')->whereNull('fases')->get();
            foreach ($existingCps as $cp) {
                $faseVal = $cp->fase ?? '';
                $detectedFases = [];
                foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $f) {
                    if (stripos($faseVal, "fase {$f}") !== false || stripos($faseVal, "fase-{$f}") !== false || $faseVal === $f) {
                        $detectedFases[] = $f;
                    }
                }
                if (empty($detectedFases)) {
                    $detectedFases = ['D']; // Default SMP
                }
                DB::table('capaian_pembelajarans')
                    ->where('id', $cp->id)
                    ->update(['fases' => json_encode($detectedFases)]);
            }
        }

        // 2. Tambah kolom fases, rekomendasi_kelas, dan rekomendasi_tingkat di tujuan_pembelajarans
        if (Schema::hasTable('tujuan_pembelajarans')) {
            Schema::table('tujuan_pembelajarans', function (Blueprint $table) {
                if (!Schema::hasColumn('tujuan_pembelajarans', 'fases')) {
                    $table->json('fases')->nullable()->after('capaian_pembelajaran_id');
                }
                if (!Schema::hasColumn('tujuan_pembelajarans', 'rekomendasi_kelas')) {
                    $table->json('rekomendasi_kelas')->nullable()->after('kelas_tingkat');
                }
                if (!Schema::hasColumn('tujuan_pembelajarans', 'rekomendasi_tingkat')) {
                    $table->json('rekomendasi_tingkat')->nullable()->after('rekomendasi_kelas');
                }
            });

            // Backfill data lama tujuan_pembelajarans
            $existingTps = DB::table('tujuan_pembelajarans')->whereNull('fases')->get();
            foreach ($existingTps as $tp) {
                $cpFases = null;
                if (!empty($tp->capaian_pembelajaran_id)) {
                    $cp = DB::table('capaian_pembelajarans')->where('id', $tp->capaian_pembelajaran_id)->first();
                    $cpFases = $cp && !empty($cp->fases) ? json_decode($cp->fases, true) : null;
                }
                $tpFases = !empty($cpFases) && is_array($cpFases) ? $cpFases : ['D'];
                $tingkat = $tp->kelas_tingkat ? [(string) $tp->kelas_tingkat] : ['7'];

                DB::table('tujuan_pembelajarans')
                    ->where('id', $tp->id)
                    ->update([
                        'fases' => json_encode($tpFases),
                        'rekomendasi_tingkat' => json_encode($tingkat),
                    ]);
            }
        }

        // 3. Tambah kolom fase, capaian_pembelajaran_id, dan link_drive_rppm di teaching_journals
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (!Schema::hasColumn('teaching_journals', 'fase')) {
                    $table->string('fase', 10)->nullable()->default('D')->after('subject_id');
                }
                if (!Schema::hasColumn('teaching_journals', 'capaian_pembelajaran_id')) {
                    $table->foreignId('capaian_pembelajaran_id')->nullable()->after('fase')->constrained('capaian_pembelajarans')->nullOnDelete();
                }
                if (!Schema::hasColumn('teaching_journals', 'link_drive_rppm')) {
                    $table->text('link_drive_rppm')->nullable()->after('rppm_id');
                }
            });

            DB::table('teaching_journals')->whereNull('fase')->update(['fase' => 'D']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (Schema::hasColumn('teaching_journals', 'link_drive_rppm')) {
                    $table->dropColumn('link_drive_rppm');
                }
                if (Schema::hasColumn('teaching_journals', 'capaian_pembelajaran_id')) {
                    $table->dropConstrainedForeignId('capaian_pembelajaran_id');
                }
                if (Schema::hasColumn('teaching_journals', 'fase')) {
                    $table->dropColumn('fase');
                }
            });
        }

        if (Schema::hasTable('tujuan_pembelajarans')) {
            Schema::table('tujuan_pembelajarans', function (Blueprint $table) {
                if (Schema::hasColumn('tujuan_pembelajarans', 'rekomendasi_tingkat')) {
                    $table->dropColumn('rekomendasi_tingkat');
                }
                if (Schema::hasColumn('tujuan_pembelajarans', 'rekomendasi_kelas')) {
                    $table->dropColumn('rekomendasi_kelas');
                }
                if (Schema::hasColumn('tujuan_pembelajarans', 'fases')) {
                    $table->dropColumn('fases');
                }
            });
        }

        if (Schema::hasTable('capaian_pembelajarans')) {
            Schema::table('capaian_pembelajarans', function (Blueprint $table) {
                if (Schema::hasColumn('capaian_pembelajarans', 'fases')) {
                    $table->dropColumn('fases');
                }
            });
        }
    }
};
