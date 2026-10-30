<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('institution_user')) {
            Schema::create('institution_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'institution_id']);
            });
        }

        // Sinkronisasi data pengguna yang sudah ada ke tabel pivot institution_user
        try {
            $users = DB::table('users')->whereNotNull('institution_id')->get(['id', 'institution_id']);
            foreach ($users as $user) {
                DB::table('institution_user')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'institution_id' => $user->institution_id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel kosong saat migrasi awal
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_user');
    }
};
