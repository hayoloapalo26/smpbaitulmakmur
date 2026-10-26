<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_competency_tests', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_competency_tests', 'tempat_lahir')) {
                $table->string('tempat_lahir')->nullable()->after('teacher_id');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'tanggal_masuk')) {
                $table->string('tanggal_masuk')->nullable()->after('tanggal_lahir');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'lama_mengajar')) {
                $table->string('lama_mengajar')->nullable()->after('tanggal_masuk');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'status_kepegawaian')) {
                $table->string('status_kepegawaian')->nullable()->after('lama_mengajar');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'niy')) {
                $table->string('niy')->nullable()->after('status_kepegawaian');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'mata_pelajaran')) {
                $table->string('mata_pelajaran')->nullable()->after('niy');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'assessor_name')) {
                $table->string('assessor_name')->nullable()->after('assessor_id');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'komponen_penilaian')) {
                $table->json('komponen_penilaian')->nullable()->after('score');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'skor_manajerial')) {
                $table->integer('skor_manajerial')->nullable()->after('komponen_penilaian');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'skor_pedagogik')) {
                $table->integer('skor_pedagogik')->nullable()->after('skor_manajerial');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'skor_profesional')) {
                $table->integer('skor_profesional')->nullable()->after('skor_pedagogik');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'skor_sosial')) {
                $table->integer('skor_sosial')->nullable()->after('skor_profesional');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'skor_kepribadian')) {
                $table->integer('skor_kepribadian')->nullable()->after('skor_sosial');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'skor_komitmen')) {
                $table->integer('skor_komitmen')->nullable()->after('skor_kepribadian');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'skor_program_unggulan')) {
                $table->integer('skor_program_unggulan')->nullable()->after('skor_komitmen');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'skor_promosi')) {
                $table->integer('skor_promosi')->nullable()->after('skor_program_unggulan');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'total_skor')) {
                $table->integer('total_skor')->nullable()->after('skor_promosi');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'nilai_akhir')) {
                $table->decimal('nilai_akhir', 5, 2)->nullable()->after('total_skor');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'predikat')) {
                $table->string('predikat')->nullable()->after('nilai_akhir');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'rekomendasi_manajerial')) {
                $table->text('rekomendasi_manajerial')->nullable()->after('predikat');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'rekomendasi_pedagogik')) {
                $table->text('rekomendasi_pedagogik')->nullable()->after('rekomendasi_manajerial');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'rekomendasi_profesional')) {
                $table->text('rekomendasi_profesional')->nullable()->after('rekomendasi_pedagogik');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'rekomendasi_sosial')) {
                $table->text('rekomendasi_sosial')->nullable()->after('rekomendasi_profesional');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'rekomendasi_kepribadian')) {
                $table->text('rekomendasi_kepribadian')->nullable()->after('rekomendasi_sosial');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'rekomendasi_komitmen')) {
                $table->text('rekomendasi_komitmen')->nullable()->after('rekomendasi_kepribadian');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'rekomendasi_program_unggulan')) {
                $table->text('rekomendasi_program_unggulan')->nullable()->after('rekomendasi_komitmen');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'rekomendasi_promosi')) {
                $table->text('rekomendasi_promosi')->nullable()->after('rekomendasi_program_unggulan');
            }
            if (!Schema::hasColumn('teacher_competency_tests', 'catatan_umum')) {
                $table->text('catatan_umum')->nullable()->after('rekomendasi_promosi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_competency_tests', function (Blueprint $table) {
            $cols = [
                'tempat_lahir',
                'tanggal_lahir',
                'tanggal_masuk',
                'lama_mengajar',
                'status_kepegawaian',
                'niy',
                'mata_pelajaran',
                'assessor_name',
                'komponen_penilaian',
                'skor_manajerial',
                'skor_pedagogik',
                'skor_profesional',
                'skor_sosial',
                'skor_kepribadian',
                'skor_komitmen',
                'skor_program_unggulan',
                'skor_promosi',
                'total_skor',
                'nilai_akhir',
                'predikat',
                'rekomendasi_manajerial',
                'rekomendasi_pedagogik',
                'rekomendasi_profesional',
                'rekomendasi_sosial',
                'rekomendasi_kepribadian',
                'rekomendasi_komitmen',
                'rekomendasi_program_unggulan',
                'rekomendasi_promosi',
                'catatan_umum',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('teacher_competency_tests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
