<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_attendances', 'substitute_teacher_id')) {
                $table->foreignId('substitute_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('teacher_attendances', 'teacher_leave_request_id')) {
                $table->foreignId('teacher_leave_request_id')->nullable()->constrained('teacher_leave_requests')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('teacher_attendances', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
            if (!Schema::hasColumn('teacher_attendances', 'attachment')) {
                $table->string('attachment')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('teacher_attendances', 'substitute_teacher_id')) $cols[] = 'substitute_teacher_id';
            if (Schema::hasColumn('teacher_attendances', 'teacher_leave_request_id')) $cols[] = 'teacher_leave_request_id';
            if (Schema::hasColumn('teacher_attendances', 'keterangan')) $cols[] = 'keterangan';
            if (Schema::hasColumn('teacher_attendances', 'attachment')) $cols[] = 'attachment';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
