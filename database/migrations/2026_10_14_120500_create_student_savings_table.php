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
        Schema::create('student_savings', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi')->nullable()->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('tanggal'); // Tanggal Menabung
            $table->string('tipe')->default('setor'); // 'setor' (menabung), 'tarik' (penarikan)
            $table->decimal('nominal', 14, 2); // Jumlah yang ditabung
            $table->decimal('saldo_awal', 14, 2)->default(0);
            $table->decimal('saldo_akhir', 14, 2)->default(0); // Saldo akhir
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete(); // Dropdown siapa bagian keuangan
            $table->string('metode_pembayaran')->default('Tunai');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'tanggal']);
            $table->index(['institution_id', 'classroom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_savings');
    }
};
