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
                    $table->text('surah_murajaah')->nullable()->change();
                    $table->string('ayah_murajaah', 255)->nullable()->change();
                });
            } catch (\Throwable $e) {
                // Abaikan jika database engine tidak memerlukan alter change
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
                    $table->string('surah_murajaah', 50)->nullable()->change();
                    $table->string('ayah_murajaah', 100)->nullable()->change();
                });
            } catch (\Throwable $e) {
                // Abaikan
            }
        }
    }
};
