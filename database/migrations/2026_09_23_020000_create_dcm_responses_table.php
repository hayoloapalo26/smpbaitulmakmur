<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dcm_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();

            // 12 Kategori Jawaban (array JSON nomor poin yang dijawab "Ya")
            $table->json('jawaban_kesehatan')->nullable();           // I. Masalah Kesehatan (25 poin)
            $table->json('jawaban_ekonomi')->nullable();             // II. Masalah Keadaan Kehidupan Ekonomi (19 poin)
            $table->json('jawaban_keluarga')->nullable();            // III. Masalah Keluarga (19 poin)
            $table->json('jawaban_agama_moral')->nullable();         // IV. Masalah Agama dan Moral (24 poin)
            $table->json('jawaban_pribadi')->nullable();             // V. Masalah Pribadi (14 poin)
            $table->json('jawaban_sosial_organisasi')->nullable();   // VI. Masalah Hubungan Sosial dan Berorganisasi (24 poin)
            $table->json('jawaban_rekreasi_hobi')->nullable();       // VII. Masalah Rekreasi, Hobi dan Penggunaan Waktu (19 poin)
            $table->json('jawaban_sekolah')->nullable();             // VIII. Masalah Penyesuaian Terhadap Sekolah (20 poin)
            $table->json('jawaban_kurikulum')->nullable();           // IX. Masalah Penyesuaian Terhadap Kurikulum (15 poin)
            $table->json('jawaban_masa_depan')->nullable();          // X. Masalah Masa Depan yang Berhubungan dengan Jabatan (11 poin)
            $table->json('jawaban_kebiasaan_belajar')->nullable();   // XI. Masalah Kebiasaan dan Belajar (15 poin)
            $table->json('jawaban_asmara')->nullable();              // XII. Masalah Muda-Mudi dan Asmara (23 poin)

            // Uraian terbuka
            $table->text('uraian_1')->nullable(); // Masalah yang belum tercantum di atas
            $table->text('uraian_2')->nullable(); // Masalah yang sedang dihadapi saat ini
            $table->text('uraian_3')->nullable(); // Dengan siapa biasanya membicarakan masalah

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dcm_responses');
    }
};
