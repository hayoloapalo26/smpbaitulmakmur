<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_materials', function (Blueprint $table) {
            $table->foreignId('wakakur_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('wakakur_approved_at')->nullable();
            $table->foreignId('kepsek_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('kepsek_approved_at')->nullable();
        });

        Schema::table('teacher_leave_requests', function (Blueprint $table) {
            $table->foreignId('wakakur_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('wakakur_approved_at')->nullable();
            $table->foreignId('kepsek_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('kepsek_approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teaching_materials', function (Blueprint $table) {
            $table->dropForeign(['wakakur_approved_by']);
            $table->dropForeign(['kepsek_approved_by']);
            $table->dropColumn(['wakakur_approved_by', 'wakakur_approved_at', 'kepsek_approved_by', 'kepsek_approved_at']);
        });

        Schema::table('teacher_leave_requests', function (Blueprint $table) {
            $table->dropForeign(['wakakur_approved_by']);
            $table->dropForeign(['kepsek_approved_by']);
            $table->dropColumn(['wakakur_approved_by', 'wakakur_approved_at', 'kepsek_approved_by', 'kepsek_approved_at']);
        });
    }
};
