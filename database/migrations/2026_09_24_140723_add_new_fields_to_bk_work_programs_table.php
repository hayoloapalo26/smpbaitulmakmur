<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bk_work_programs', function (Blueprint $table) {
            $table->string('tahun_ajaran')->default('2026/2027');
            $table->string('tipe_pelaksanaan')->default('Tahunan'); 
            $table->string('sifat')->default('Wajib'); 
            $table->text('teknis_pelaksanaan')->nullable();
            $table->string('dokumen_terkait')->nullable();
            $table->integer('estimasi_anggaran')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bk_work_programs', function (Blueprint $table) {
            $table->dropColumn([
                'tahun_ajaran',
                'tipe_pelaksanaan',
                'sifat',
                'teknis_pelaksanaan',
                'dokumen_terkait',
                'estimasi_anggaran'
            ]);
        });
    }
};
