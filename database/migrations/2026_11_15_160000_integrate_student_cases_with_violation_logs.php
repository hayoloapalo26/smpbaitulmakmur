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
        // 1. Tambah kolom poin_pelanggaran ke student_cases jika belum ada
        Schema::table('student_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('student_cases', 'poin_pelanggaran')) {
                $table->integer('poin_pelanggaran')->default(5)->after('deskripsi');
            }
        });

        // 2. Tambah foreign key student_case_id ke student_violation_logs jika belum ada
        Schema::table('student_violation_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('student_violation_logs', 'student_case_id')) {
                $table->foreignId('student_case_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('student_cases')
                    ->cascadeOnDelete();
            }
        });

        // 3. Sinkronisasikan kasus yang sudah ada ke student_violation_logs agar data historis terintegrasi
        try {
            $cases = \App\Models\StudentCase::with('student')->get();
            foreach ($cases as $case) {
                if (!$case->student_id) continue;

                $exists = \App\Models\StudentViolationLog::where('student_case_id', $case->id)->exists();
                if (!$exists) {
                    $kategoriText = is_array($case->kategori_kasus)
                        ? implode(', ', $case->kategori_kasus)
                        : ($case->kategori_kasus ?? 'Pelanggaran Disiplin');

                    \App\Models\StudentViolationLog::create([
                        'student_case_id'        => $case->id,
                        'tanggal'                => $case->tanggal_kejadian ?? ($case->created_at ? $case->created_at->toDateString() : date('Y-m-d')),
                        'jam'                    => $case->created_at ? $case->created_at->format('H:i') : '07:30',
                        'student_id'             => $case->student_id,
                        'classroom_id'           => $case->student?->classroom_id,
                        'bentuk_pelanggaran'     => "Kasus ({$case->nomor_kasus}): {$kategoriText}" . ($case->deskripsi ? " - " . \Illuminate\Support\Str::limit($case->deskripsi, 80) : ''),
                        'poin'                   => $case->poin_pelanggaran ?? 5,
                        'teacher_id'             => $case->wali_kelas_id ?? 1,
                        'tindak_lanjut_langsung' => $case->catatan_wali_kelas ?: ($case->deskripsi ?: 'Dicatat dalam rekam jejak kasus pelanggaran siswa'),
                        'acc_piket'              => true,
                        'acc_piket_at'           => now(),
                        'institution_id'         => $case->institution_id ?? $case->student?->institution_id,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Jangan menghentikan migrasi jika tabel students belum terisi saat fresh test
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_violation_logs', function (Blueprint $table) {
            if (Schema::hasColumn('student_violation_logs', 'student_case_id')) {
                try {
                    $table->dropForeign(['student_case_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('student_case_id');
            }
        });

        Schema::table('student_cases', function (Blueprint $table) {
            if (Schema::hasColumn('student_cases', 'poin_pelanggaran')) {
                $table->dropColumn('poin_pelanggaran');
            }
        });
    }
};
