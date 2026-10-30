<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('teacher_observations')) {
            Schema::create('teacher_observations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('observer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('tanggal_observasi');
                $table->foreignId('mata_pelajaran_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
                $table->string('materi_pembelajaran')->nullable();
                $table->string('fokus_observasi')->nullable();
                $table->json('aspek_penilaian')->nullable();
                $table->decimal('score', 5, 2)->default(0);
                $table->string('predikat', 50)->nullable();
                $table->text('catatan_observasi')->nullable();
                $table->text('rekomendasi')->nullable();
                $table->string('status', 30)->default('selesai');
                $table->timestamps();

                $table->index(['teacher_id', 'tanggal_observasi']);
            });
        }

        // Ensure roles for Pengembang & Penjamin Mutu exist in database
        try {
            Role::firstOrCreate(['name' => 'tim_pengembang_penjamin_mutu_yayasan', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'pengembang_penjamin_mutu', 'guard_name' => 'web']);
        } catch (\Throwable $e) {
            // Ignored if roles table not yet migrated during test bootstrapping
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_observations');
    }
};
