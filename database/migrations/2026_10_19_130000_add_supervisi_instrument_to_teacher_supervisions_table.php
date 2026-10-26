<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_supervisions', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_supervisions', 'unit_sekolah_program')) {
                $table->string('unit_sekolah_program')->nullable()->after('catatan');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'mata_pelajaran')) {
                $table->string('mata_pelajaran')->nullable()->after('unit_sekolah_program');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'kelas_semester')) {
                $table->string('kelas_semester')->nullable()->after('mata_pelajaran');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'supervisor_name')) {
                $table->string('supervisor_name')->nullable()->after('supervisor_id');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'komponen_penilaian')) {
                $table->json('komponen_penilaian')->nullable()->after('supervisor_name');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'total_skor')) {
                $table->integer('total_skor')->nullable()->after('komponen_penilaian');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'skor_perencanaan')) {
                $table->integer('skor_perencanaan')->nullable()->after('total_skor');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'skor_pelaksanaan')) {
                $table->integer('skor_pelaksanaan')->nullable()->after('skor_perencanaan');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'skor_evaluasi')) {
                $table->integer('skor_evaluasi')->nullable()->after('skor_pelaksanaan');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'nilai_akhir')) {
                $table->decimal('nilai_akhir', 5, 2)->nullable()->after('skor_evaluasi');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'predikat')) {
                $table->string('predikat')->nullable()->after('nilai_akhir');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'praktik_baik')) {
                $table->text('praktik_baik')->nullable()->after('predikat');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'area_fokus_perbaikan')) {
                $table->text('area_fokus_perbaikan')->nullable()->after('praktik_baik');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'rencana_coaching')) {
                $table->text('rencana_coaching')->nullable()->after('area_fokus_perbaikan');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'rekomendasi_perencanaan')) {
                $table->text('rekomendasi_perencanaan')->nullable()->after('rencana_coaching');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'rekomendasi_pelaksanaan')) {
                $table->text('rekomendasi_pelaksanaan')->nullable()->after('rekomendasi_perencanaan');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'rekomendasi_evaluasi')) {
                $table->text('rekomendasi_evaluasi')->nullable()->after('rekomendasi_pelaksanaan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_supervisions', function (Blueprint $table) {
            $cols = [
                'unit_sekolah_program',
                'mata_pelajaran',
                'kelas_semester',
                'supervisor_name',
                'komponen_penilaian',
                'total_skor',
                'skor_perencanaan',
                'skor_pelaksanaan',
                'skor_evaluasi',
                'nilai_akhir',
                'predikat',
                'praktik_baik',
                'area_fokus_perbaikan',
                'rencana_coaching',
                'rekomendasi_perencanaan',
                'rekomendasi_pelaksanaan',
                'rekomendasi_evaluasi',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('teacher_supervisions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
