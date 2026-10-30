<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (!Schema::hasColumn('teaching_journals', 'rppm_id')) {
                    $table->foreignId('rppm_id')->nullable()->after('subject_id')->constrained('rppms')->nullOnDelete();
                }
                if (!Schema::hasColumn('teaching_journals', 'tp_ids')) {
                    $table->json('tp_ids')->nullable()->after('rppm_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (Schema::hasColumn('teaching_journals', 'tp_ids')) {
                    $table->dropColumn('tp_ids');
                }
                if (Schema::hasColumn('teaching_journals', 'rppm_id')) {
                    $table->dropConstrainedForeignId('rppm_id');
                }
            });
        }
    }
};
