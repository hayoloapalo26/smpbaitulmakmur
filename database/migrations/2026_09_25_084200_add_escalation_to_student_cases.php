<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_cases', function (Blueprint $table) {
            // Kolom yang sudah ada: student_id, wali_kelas_id, deskripsi, recommended_to_bk, status, follow_up_note
            // Tambahan kolom baru untuk alur eskalasi profesional

            // Kolom alur eskalasi
            $table->string('nomor_kasus')->nullable()->after('id'); // No. Kasus otomatis
            $table->date('tanggal_kejadian')->nullable()->after('nomor_kasus');

            // Penerima kasus (escalation chain)
            $table->boolean('recommended_to_kesiswaan')->default(false)->after('recommended_to_bk');
            $table->boolean('recommended_to_kepsek')->default(false)->after('recommended_to_kesiswaan');

            // Siapa yang menangani sekarang
            $table->string('ditangani_oleh')->nullable()->after('recommended_to_kepsek');
            // 'wali_kelas' | 'bk' | 'kesiswaan' | 'kepala_sekolah'

            // Catatan per level handler
            $table->text('catatan_wali_kelas')->nullable()->after('follow_up_note');
            $table->text('catatan_bk')->nullable()->after('catatan_wali_kelas');
            $table->text('catatan_kesiswaan')->nullable()->after('catatan_bk');
            $table->text('catatan_kepsek')->nullable()->after('catatan_kesiswaan');

            // Tanggal setiap aksi eskalasi
            $table->timestamp('diteruskan_ke_bk_at')->nullable()->after('catatan_kepsek');
            $table->timestamp('diteruskan_ke_kesiswaan_at')->nullable()->after('diteruskan_ke_bk_at');
            $table->timestamp('diteruskan_ke_kepsek_at')->nullable()->after('diteruskan_ke_kesiswaan_at');
            $table->timestamp('diselesaikan_at')->nullable()->after('diteruskan_ke_kepsek_at');

            // Siapa yang menandai selesai
            $table->foreignId('diselesaikan_oleh')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_cases', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_kasus', 'tanggal_kejadian',
                'recommended_to_kesiswaan', 'recommended_to_kepsek',
                'ditangani_oleh',
                'catatan_wali_kelas', 'catatan_bk', 'catatan_kesiswaan', 'catatan_kepsek',
                'diteruskan_ke_bk_at', 'diteruskan_ke_kesiswaan_at',
                'diteruskan_ke_kepsek_at', 'diselesaikan_at',
                'diselesaikan_oleh',
            ]);
        });
    }
};
