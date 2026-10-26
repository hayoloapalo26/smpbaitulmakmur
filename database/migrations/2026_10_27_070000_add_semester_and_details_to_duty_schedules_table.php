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
        Schema::table('duty_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('duty_schedules', 'institution_id')) {
                $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
            }
            if (!Schema::hasColumn('duty_schedules', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('teacher_id')->constrained('academic_years')->nullOnDelete();
            }
            if (!Schema::hasColumn('duty_schedules', 'semester')) {
                $table->string('semester', 20)->nullable()->after('academic_year_id');
            }
            if (!Schema::hasColumn('duty_schedules', 'hari')) {
                $table->string('hari', 20)->nullable()->after('semester');
            }
            if (!Schema::hasColumn('duty_schedules', 'notes')) {
                $table->text('notes')->nullable()->after('tanggal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('duty_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('duty_schedules', 'institution_id')) {
                $table->dropForeign(['institution_id']);
                $table->dropColumn('institution_id');
            }
            if (Schema::hasColumn('duty_schedules', 'academic_year_id')) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            }
            if (Schema::hasColumn('duty_schedules', 'semester')) {
                $table->dropColumn('semester');
            }
            if (Schema::hasColumn('duty_schedules', 'hari')) {
                $table->dropColumn('hari');
            }
            if (Schema::hasColumn('duty_schedules', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
