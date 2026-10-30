<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quran_achievements')) {
            Schema::table('quran_achievements', function (Blueprint $table) {
                if (!Schema::hasColumn('quran_achievements', 'scale_score')) {
                    $table->integer('scale_score')->nullable()->after('predikat');
                }
            });
        }

        // Migrate existing scale_score from reading_quality_scores to quran_achievements if any
        if (Schema::hasTable('reading_quality_scores') && Schema::hasTable('quran_achievements')) {
            $scores = DB::table('reading_quality_scores')->get();
            foreach ($scores as $s) {
                DB::table('quran_achievements')
                    ->where('student_id', $s->student_id)
                    ->where('tanggal', $s->tanggal)
                    ->whereNull('scale_score')
                    ->limit(1)
                    ->update(['scale_score' => $s->scale_score]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quran_achievements') && Schema::hasColumn('quran_achievements', 'scale_score')) {
            Schema::table('quran_achievements', function (Blueprint $table) {
                $table->dropColumn('scale_score');
            });
        }
    }
};
