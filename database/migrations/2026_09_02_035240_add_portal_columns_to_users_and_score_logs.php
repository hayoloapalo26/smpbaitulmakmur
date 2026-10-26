<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->nullOnDelete();
        });

        Schema::table('character_score_logs', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['guardian_id']);
            $table->dropColumn(['student_id', 'guardian_id']);
        });

        Schema::table('character_score_logs', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }
};
