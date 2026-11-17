<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_attendances', 'late_penalty_points')) {
                $table->integer('late_penalty_points')->default(5)->after('is_late');
            }
            if (!Schema::hasColumn('daily_attendances', 'late_approval_status')) {
                $table->string('late_approval_status', 20)->nullable()->default(null)->after('late_penalty_points')->index();
            }
            if (!Schema::hasColumn('daily_attendances', 'late_approved_by')) {
                $table->foreignId('late_approved_by')->nullable()->after('late_approval_status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('daily_attendances', 'late_approved_at')) {
                $table->dateTime('late_approved_at')->nullable()->after('late_approved_by');
            }
            if (!Schema::hasColumn('daily_attendances', 'late_rejection_reason')) {
                $table->text('late_rejection_reason')->nullable()->after('late_approved_at');
            }
            if (!Schema::hasColumn('daily_attendances', 'late_violation_log_id')) {
                $table->foreignId('late_violation_log_id')->nullable()->after('late_rejection_reason')->constrained('student_violation_logs')->nullOnDelete();
            }
        });

        // Set status pending untuk record keterlambatan yang sudah ada dan belum ditentukan statusnya
        try {
            DB::table('daily_attendances')
                ->where('tipe_absen', 'masuk')
                ->where('is_late', true)
                ->whereNull('late_approval_status')
                ->update([
                    'late_penalty_points'  => 5,
                    'late_approval_status' => 'pending',
                ]);
        } catch (\Throwable $e) {
            // Abaikan jika tabel kosong
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('daily_attendances', 'late_violation_log_id')) {
                $table->dropForeign(['late_violation_log_id']);
                $table->dropColumn('late_violation_log_id');
            }
            if (Schema::hasColumn('daily_attendances', 'late_approved_by')) {
                $table->dropForeign(['late_approved_by']);
                $table->dropColumn('late_approved_by');
            }
            $columnsToDrop = [];
            foreach (['late_penalty_points', 'late_approval_status', 'late_approved_at', 'late_rejection_reason'] as $col) {
                if (Schema::hasColumn('daily_attendances', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
