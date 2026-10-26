<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_supervisions', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_supervisions', 'institution_id')) {
                $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
            }
            if (!Schema::hasColumn('teacher_supervisions', 'status')) {
                $table->string('status')->default('menunggu')->after('catatan');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'jadwal')) {
                $table->dateTime('jadwal')->nullable()->after('status');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'catatan_yayasan')) {
                $table->text('catatan_yayasan')->nullable()->after('jadwal');
            }
            if (!Schema::hasColumn('teacher_supervisions', 'score')) {
                $table->decimal('score', 5, 2)->nullable()->after('catatan_yayasan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_supervisions', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_supervisions', 'institution_id')) {
                $table->dropForeign(['institution_id']);
                $table->dropColumn('institution_id');
            }
            if (Schema::hasColumn('teacher_supervisions', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('teacher_supervisions', 'jadwal')) {
                $table->dropColumn('jadwal');
            }
            if (Schema::hasColumn('teacher_supervisions', 'catatan_yayasan')) {
                $table->dropColumn('catatan_yayasan');
            }
            if (Schema::hasColumn('teacher_supervisions', 'score')) {
                $table->dropColumn('score');
            }
        });
    }
};
