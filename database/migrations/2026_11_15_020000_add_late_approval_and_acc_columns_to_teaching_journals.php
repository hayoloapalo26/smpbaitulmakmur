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
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (!Schema::hasColumn('teaching_journals', 'is_late')) {
                    $table->boolean('is_late')->default(false)->after('materi');
                }
                if (!Schema::hasColumn('teaching_journals', 'late_status')) {
                    $table->string('late_status')->default('on_time')->after('is_late'); // 'on_time', 'pending_waka', 'approved_waka', 'rejected_waka'
                }
                if (!Schema::hasColumn('teaching_journals', 'waka_approved_by')) {
                    $table->foreignId('waka_approved_by')->nullable()->after('late_status')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('teaching_journals', 'waka_approved_at')) {
                    $table->dateTime('waka_approved_at')->nullable()->after('waka_approved_by');
                }
                if (!Schema::hasColumn('teaching_journals', 'waka_notes')) {
                    $table->text('waka_notes')->nullable()->after('waka_approved_at');
                }
                if (!Schema::hasColumn('teaching_journals', 'status_acc_kepsek')) {
                    $table->string('status_acc_kepsek')->default('pending')->after('waka_notes'); // 'pending', 'approved', 'need_revision'
                }
                if (!Schema::hasColumn('teaching_journals', 'catatan_kepala_sekolah')) {
                    $table->text('catatan_kepala_sekolah')->nullable()->after('status_acc_kepsek');
                }
                if (!Schema::hasColumn('teaching_journals', 'kepsek_approved_by')) {
                    $table->foreignId('kepsek_approved_by')->nullable()->after('catatan_kepala_sekolah')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('teaching_journals', 'kepsek_approved_at')) {
                    $table->dateTime('kepsek_approved_at')->nullable()->after('kepsek_approved_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                $columns = [
                    'kepsek_approved_at',
                    'kepsek_approved_by',
                    'catatan_kepala_sekolah',
                    'status_acc_kepsek',
                    'waka_notes',
                    'waka_approved_at',
                    'waka_approved_by',
                    'late_status',
                    'is_late',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('teaching_journals', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
