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
            Schema::table('quran_achievements', function (Blueprint $table) {
                if (!Schema::hasColumn('quran_achievements', 'juz_murajaah')) {
                    $table->integer('juz_murajaah')->nullable()->after('ayah');
                }
                if (!Schema::hasColumn('quran_achievements', 'surah_murajaah')) {
                    $table->string('surah_murajaah', 50)->nullable()->after('juz_murajaah');
                }
                if (!Schema::hasColumn('quran_achievements', 'ayah_murajaah')) {
                    $table->string('ayah_murajaah', 100)->nullable()->after('surah_murajaah');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quran_achievements')) {
            Schema::table('quran_achievements', function (Blueprint $table) {
                if (Schema::hasColumn('quran_achievements', 'ayah_murajaah')) {
                    $table->dropColumn('ayah_murajaah');
                }
                if (Schema::hasColumn('quran_achievements', 'surah_murajaah')) {
                    $table->dropColumn('surah_murajaah');
                }
                if (Schema::hasColumn('quran_achievements', 'juz_murajaah')) {
                    $table->dropColumn('juz_murajaah');
                }
            });
        }
    }
};
