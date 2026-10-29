<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reading_quality_scores')) {
            Schema::table('reading_quality_scores', function (Blueprint $table) {
                // Change enum to string so we can support all 4 Quran specializations
                $table->string('program', 50)->change();

                if (!Schema::hasColumn('reading_quality_scores', 'jilid_halaman')) {
                    $table->string('jilid_halaman')->nullable()->after('scale_score');
                }
                if (!Schema::hasColumn('reading_quality_scores', 'catatan_guru')) {
                    $table->text('catatan_guru')->nullable()->after('jilid_halaman');
                }
                if (!Schema::hasColumn('reading_quality_scores', 'predikat')) {
                    $table->string('predikat')->nullable()->after('catatan_guru');
                }
            });
        }

        if (Schema::hasTable('quran_achievements')) {
            Schema::table('quran_achievements', function (Blueprint $table) {
                if (!Schema::hasColumn('quran_achievements', 'program')) {
                    $table->string('program', 50)->nullable()->after('type');
                }
                if (!Schema::hasColumn('quran_achievements', 'jilid_halaman')) {
                    $table->string('jilid_halaman')->nullable()->after('ayah');
                }
                if (!Schema::hasColumn('quran_achievements', 'predikat')) {
                    $table->string('predikat')->nullable()->after('catatan');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reading_quality_scores')) {
            Schema::table('reading_quality_scores', function (Blueprint $table) {
                if (Schema::hasColumn('reading_quality_scores', 'predikat')) {
                    $table->dropColumn('predikat');
                }
                if (Schema::hasColumn('reading_quality_scores', 'catatan_guru')) {
                    $table->dropColumn('catatan_guru');
                }
                if (Schema::hasColumn('reading_quality_scores', 'jilid_halaman')) {
                    $table->dropColumn('jilid_halaman');
                }
            });
        }

        if (Schema::hasTable('quran_achievements')) {
            Schema::table('quran_achievements', function (Blueprint $table) {
                if (Schema::hasColumn('quran_achievements', 'predikat')) {
                    $table->dropColumn('predikat');
                }
                if (Schema::hasColumn('quran_achievements', 'jilid_halaman')) {
                    $table->dropColumn('jilid_halaman');
                }
                if (Schema::hasColumn('quran_achievements', 'program')) {
                    $table->dropColumn('program');
                }
            });
        }
    }
};
