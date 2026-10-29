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
            'program_kepseks',
            'program_kurikulums',
            'program_kesiswaans',
            'program_sarpras',
            'program_humas',
            'bk_work_programs',
            'class_programs',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'link_drive_dokumen')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->text('link_drive_dokumen')->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'program_kepseks',
            'program_kurikulums',
            'program_kesiswaans',
            'program_sarpras',
            'program_humas',
            'bk_work_programs',
            'class_programs',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'link_drive_dokumen')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('link_drive_dokumen');
                });
            }
        }
    }
};
