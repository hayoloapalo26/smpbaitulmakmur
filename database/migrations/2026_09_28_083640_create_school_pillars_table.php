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
        Schema::create('school_pillars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('school_pillar_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_pillar_id')->constrained('school_pillars')->cascadeOnDelete();
            $table->string('code')->nullable(); // e.g. "1.1", "1.2"
            $table->string('name'); // Nama kegiatan/SOP
            $table->string('assigned_role')->nullable(); // KS, WKr, WKs, SP, Hm, BK, TU
            $table->boolean('is_sop_merah')->default(false); // Penanda khusus yg di-highlight merah
            $table->enum('status', ['belum', 'proses', 'selesai'])->default('belum');
            $table->string('file_bukti')->nullable(); // Upload file PDF dll
            $table->text('catatan')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_pillar_tasks');
        Schema::dropIfExists('school_pillars');
    }
};
