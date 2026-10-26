<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bk_work_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul_program');
            $table->string('bidang_layanan'); // e.g. Pribadi, Sosial, Belajar, Karir
            $table->string('sasaran');
            $table->string('waktu_pelaksanaan');
            $table->text('tujuan');
            $table->string('status')->default('Rencana');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bk_work_programs');
    }
};
