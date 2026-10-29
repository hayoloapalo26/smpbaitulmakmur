<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_evaluations')) {
            Schema::table('program_evaluations', function (Blueprint $table) {
                if (!Schema::hasColumn('program_evaluations', 'program_source_type')) {
                    $table->string('program_source_type')->nullable()->after('nama_program');
                }
                if (!Schema::hasColumn('program_evaluations', 'program_source_id')) {
                    $table->unsignedBigInteger('program_source_id')->nullable()->after('program_source_type');
                }
                if (!Schema::hasColumn('program_evaluations', 'reviewed_by')) {
                    $table->foreignId('reviewed_by')->nullable()->after('catatan_kepsek')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('program_evaluations', 'tanggal_review')) {
                    $table->timestamp('tanggal_review')->nullable()->after('reviewed_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('program_evaluations')) {
            Schema::table('program_evaluations', function (Blueprint $table) {
                if (Schema::hasColumn('program_evaluations', 'tanggal_review')) {
                    $table->dropColumn('tanggal_review');
                }
                if (Schema::hasColumn('program_evaluations', 'reviewed_by')) {
                    $table->dropConstrainedForeignId('reviewed_by');
                }
                if (Schema::hasColumn('program_evaluations', 'program_source_id')) {
                    $table->dropColumn('program_source_id');
                }
                if (Schema::hasColumn('program_evaluations', 'program_source_type')) {
                    $table->dropColumn('program_source_type');
                }
            });
        }
    }
};
