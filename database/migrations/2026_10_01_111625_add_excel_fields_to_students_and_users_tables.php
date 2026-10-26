<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('no_kk')->nullable();
            $table->string('agama')->nullable();
            $table->integer('jumlah_saudara_kandung')->nullable(); // or just use jumlah_saudara
            
            // Father
            $table->string('pendidikan_ayah')->nullable();
            $table->string('penghasilan_ayah')->nullable();
            $table->string('no_hp_ayah')->nullable();

            // Mother
            $table->string('pendidikan_ibu')->nullable();
            $table->string('penghasilan_ibu')->nullable();
            $table->string('no_hp_ibu')->nullable();

            // Guardian
            $table->string('nama_wali')->nullable();
            $table->string('no_hp_wali')->nullable();
            $table->string('pendidikan_wali')->nullable();
            $table->string('pekerjaan_wali')->nullable();
            $table->string('penghasilan_wali')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('satminkal')->nullable(); // KET SATMINKAL
        });
    }

    public function down(): void
    {
        // 
    }
};
