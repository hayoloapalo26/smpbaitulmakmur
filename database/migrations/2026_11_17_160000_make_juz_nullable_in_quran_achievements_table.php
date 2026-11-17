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
        if (Schema::hasTable('quran_achievements')) {
            try {
                Schema::table('quran_achievements', function (Blueprint $table) {
                    $table->integer('juz')->nullable()->change();
                });
            } catch (\Throwable $e) {
                // Abaikan jika database engine tidak memerlukan change
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quran_achievements')) {
            try {
                Schema::table('quran_achievements', function (Blueprint $table) {
                    $table->integer('juz')->default(30)->change();
                });
            } catch (\Throwable $e) {
                // Abaikan
            }
        }
    }
};
