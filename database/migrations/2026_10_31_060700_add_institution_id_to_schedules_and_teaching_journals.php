<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedules') && !Schema::hasColumn('schedules', 'institution_id')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
            });

            try {
                $schedules = DB::table('schedules')->whereNull('institution_id')->whereNotNull('classroom_id')->get(['id', 'classroom_id']);
                foreach ($schedules as $s) {
                    $instId = DB::table('classrooms')->where('id', $s->classroom_id)->value('institution_id');
                    if ($instId) {
                        DB::table('schedules')->where('id', $s->id)->update(['institution_id' => $instId]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('teaching_journals') && !Schema::hasColumn('teaching_journals', 'institution_id')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
            });

            try {
                $journals = DB::table('teaching_journals')->whereNull('institution_id')->whereNotNull('classroom_id')->get(['id', 'classroom_id']);
                foreach ($journals as $j) {
                    $instId = DB::table('classrooms')->where('id', $j->classroom_id)->value('institution_id');
                    if ($instId) {
                        DB::table('teaching_journals')->where('id', $j->id)->update(['institution_id' => $instId]);
                    }
                }
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teaching_journals') && Schema::hasColumn('teaching_journals', 'institution_id')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                $table->dropForeign(['institution_id']);
                $table->dropColumn('institution_id');
            });
        }

        if (Schema::hasTable('schedules') && Schema::hasColumn('schedules', 'institution_id')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->dropForeign(['institution_id']);
                $table->dropColumn('institution_id');
            });
        }
    }
};
