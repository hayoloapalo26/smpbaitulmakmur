<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_supervisions')) {
            if (!Schema::hasColumn('teacher_supervisions', 'notif_dismissed_at')) {
                Schema::table('teacher_supervisions', function (Blueprint $table) {
                    $table->timestamp('notif_dismissed_at')->nullable()->after('catatan_yayasan');
                });
            }
        }

        if (Schema::hasTable('teacher_competency_tests')) {
            if (!Schema::hasColumn('teacher_competency_tests', 'notif_dismissed_at')) {
                Schema::table('teacher_competency_tests', function (Blueprint $table) {
                    $table->timestamp('notif_dismissed_at')->nullable()->after('catatan_yayasan');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teacher_supervisions')) {
            if (Schema::hasColumn('teacher_supervisions', 'notif_dismissed_at')) {
                Schema::table('teacher_supervisions', function (Blueprint $table) {
                    $table->dropColumn('notif_dismissed_at');
                });
            }
        }

        if (Schema::hasTable('teacher_competency_tests')) {
            if (Schema::hasColumn('teacher_competency_tests', 'notif_dismissed_at')) {
                Schema::table('teacher_competency_tests', function (Blueprint $table) {
                    $table->dropColumn('notif_dismissed_at');
                });
            }
        }
    }
};
