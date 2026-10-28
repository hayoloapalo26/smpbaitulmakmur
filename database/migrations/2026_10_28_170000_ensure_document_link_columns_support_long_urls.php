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
        $tables = [
            'document_archives'     => ['file'],
            'letters'               => ['file'],
            'meeting_minutes'       => ['file'],
            'class_programs'        => ['dokumen_pendukung'],
            'osis_programs'         => ['proposal_file', 'lpj_file'],
            'parent_summons'        => ['surat_file'],
            'daily_attendances'     => ['bukti_izin'],
            'program_evaluations'   => ['file_evaluasi'],
            'student_achievements'  => ['sertifikat'],
            'teacher_attendances'   => ['attachment'],
            'teacher_leave_requests'=> ['supporting_letter'],
            'work_programs'         => ['file_proposal', 'file_lpj'],
        ];

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $tableBlueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $tableBlueprint->text($column)->nullable()->change();
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tetap text / nullable
    }
};
