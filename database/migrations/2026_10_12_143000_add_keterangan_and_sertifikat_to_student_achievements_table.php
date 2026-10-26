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
        Schema::table('student_achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('student_achievements', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('tingkat');
            }
            if (!Schema::hasColumn('student_achievements', 'sertifikat')) {
                $table->string('sertifikat')->nullable()->after('keterangan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_achievements', function (Blueprint $table) {
            if (Schema::hasColumn('student_achievements', 'sertifikat')) {
                $table->dropColumn('sertifikat');
            }
            if (Schema::hasColumn('student_achievements', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
        });
    }
};
