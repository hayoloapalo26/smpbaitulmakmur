<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alumni_tracers', function (Blueprint $table) {
            if (!Schema::hasColumn('alumni_tracers', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('id')->constrained('students')->nullOnDelete();
            }
            if (!Schema::hasColumn('alumni_tracers', 'nis')) {
                $table->string('nis')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('alumni_tracers', 'nisn')) {
                $table->string('nisn')->nullable()->after('nis');
            }
            if (!Schema::hasColumn('alumni_tracers', 'status_kegiatan')) {
                $table->string('status_kegiatan')->default('melanjutkan_pendidikan')->after('tahun_lulus');
            }
            if (!Schema::hasColumn('alumni_tracers', 'nama_instansi')) {
                $table->string('nama_instansi')->nullable()->after('status_kegiatan');
            }
            if (!Schema::hasColumn('alumni_tracers', 'jurusan_bidang')) {
                $table->string('jurusan_bidang')->nullable()->after('nama_instansi');
            }
            if (!Schema::hasColumn('alumni_tracers', 'no_telepon')) {
                $table->string('no_telepon')->nullable()->after('jurusan_bidang');
            }
            if (!Schema::hasColumn('alumni_tracers', 'email')) {
                $table->string('email')->nullable()->after('no_telepon');
            }
            if (!Schema::hasColumn('alumni_tracers', 'alamat_domisili')) {
                $table->string('alamat_domisili')->nullable()->after('email');
            }
            if (!Schema::hasColumn('alumni_tracers', 'prestasi_setelah_lulus')) {
                $table->text('prestasi_setelah_lulus')->nullable()->after('alamat_domisili');
            }
            if (!Schema::hasColumn('alumni_tracers', 'kesan_pesan')) {
                $table->text('kesan_pesan')->nullable()->after('prestasi_setelah_lulus');
            }
            if (!Schema::hasColumn('alumni_tracers', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('kesan_pesan');
            }
            if (!Schema::hasColumn('alumni_tracers', 'input_by')) {
                $table->foreignId('input_by')->nullable()->after('keterangan')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumni_tracers', function (Blueprint $table) {
            $columns = [
                'student_id', 'nis', 'nisn', 'status_kegiatan', 'nama_instansi',
                'jurusan_bidang', 'no_telepon', 'email', 'alamat_domisili',
                'prestasi_setelah_lulus', 'kesan_pesan', 'keterangan', 'input_by'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('alumni_tracers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
