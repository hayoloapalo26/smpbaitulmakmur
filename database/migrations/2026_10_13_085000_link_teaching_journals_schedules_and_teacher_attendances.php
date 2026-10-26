<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            if (!Schema::hasColumn('teaching_journals', 'schedule_id')) {
                $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            }
        });

        Schema::table('teacher_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_attendances', 'schedule_id')) {
                $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            }
            if (!Schema::hasColumn('teacher_attendances', 'teaching_journal_id')) {
                $table->foreignId('teaching_journal_id')->nullable()->constrained('teaching_journals')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('teacher_attendances', 'classroom_id')) {
                $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            }
            if (!Schema::hasColumn('teacher_attendances', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            }
            if (!Schema::hasColumn('teacher_attendances', 'jam_ke')) {
                $table->integer('jam_ke')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropColumn(['schedule_id', 'teaching_journal_id', 'classroom_id', 'subject_id', 'jam_ke']);
        });

        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->dropColumn(['schedule_id']);
        });
    }
};
