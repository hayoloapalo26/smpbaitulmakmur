<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('tempat_lahir')->nullable();
            $table->text('alamat_siswa')->nullable();
            $table->string('no_telepon_siswa')->nullable();
            $table->string('asal_sekolah')->nullable();
            $table->string('nik')->nullable();
            $table->string('no_ijazah')->nullable();
            $table->string('no_skhu')->nullable();
            $table->string('tahun_lulus')->nullable();
            $table->string('no_peserta_un')->nullable();
            
            $table->string('nama_ayah')->nullable();
            $table->string('tempat_lahir_ayah')->nullable();
            $table->date('tanggal_lahir_ayah')->nullable();
            $table->string('pekerjaan_ayah')->nullable();
            
            $table->string('nama_ibu')->nullable();
            $table->string('tempat_lahir_ibu')->nullable();
            $table->date('tanggal_lahir_ibu')->nullable();
            $table->string('pekerjaan_ibu')->nullable();
            
            $table->string('no_telepon_orang_tua')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'tempat_lahir', 'alamat_siswa', 'no_telepon_siswa', 'asal_sekolah', 'nik',
                'no_ijazah', 'no_skhu', 'tahun_lulus', 'no_peserta_un',
                'nama_ayah', 'tempat_lahir_ayah', 'tanggal_lahir_ayah', 'pekerjaan_ayah',
                'nama_ibu', 'tempat_lahir_ibu', 'tanggal_lahir_ibu', 'pekerjaan_ibu',
                'no_telepon_orang_tua'
            ]);
        });
    }
};
