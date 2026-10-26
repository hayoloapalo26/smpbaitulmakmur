<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->string('rencana_kegiatan_guru')->nullable();
            $table->string('kegiatan_siswa')->nullable();
            $table->text('refleksi_guru')->nullable();
            $table->text('tindak_lanjut')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->dropColumn(['rencana_kegiatan_guru', 'kegiatan_siswa', 'refleksi_guru', 'tindak_lanjut']);
        });
    }
};
