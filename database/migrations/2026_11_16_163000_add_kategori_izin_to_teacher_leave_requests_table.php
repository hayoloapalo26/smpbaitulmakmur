<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_leave_requests', 'kategori_izin')) {
                $table->string('kategori_izin', 50)->default('izin')->after('teacher_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_leave_requests', 'kategori_izin')) {
                $table->dropColumn('kategori_izin');
            }
        });
    }
};
