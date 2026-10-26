<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('no_induk_yayasan')->nullable();
            $table->string('nuptk')->nullable();
            $table->enum('gender', ['L', 'P'])->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenjang_pendidikan')->nullable();
            $table->string('jurusan_pendidikan')->nullable();
            $table->string('universitas')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('status_kepegawaian')->nullable(); // avoiding 'status' since it's generic
            $table->string('no_sk_gty')->nullable();
            $table->string('no_ukg')->nullable();
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'no_induk_yayasan', 'nuptk', 'gender', 'tempat_lahir', 'tanggal_lahir',
                'jenjang_pendidikan', 'jurusan_pendidikan', 'universitas', 'tanggal_masuk',
                'jabatan', 'status_kepegawaian', 'no_sk_gty', 'no_ukg', 'no_hp', 'alamat'
            ]);
        });
    }
};
