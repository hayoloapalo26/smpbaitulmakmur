<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (!Schema::hasColumn('grades', 'rppm_id')) {
                    $table->foreignId('rppm_id')->nullable()->after('subject_id')->constrained('rppms')->nullOnDelete();
                }
                if (!Schema::hasColumn('grades', 'catatan_per_tp')) {
                    $table->json('catatan_per_tp')->nullable()->after('catatan_guru');
                }
                if (!Schema::hasColumn('grades', 'tp_remedial_ids')) {
                    $table->json('tp_remedial_ids')->nullable()->after('catatan_remedial');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (Schema::hasColumn('grades', 'tp_remedial_ids')) {
                    $table->dropColumn('tp_remedial_ids');
                }
                if (Schema::hasColumn('grades', 'catatan_per_tp')) {
                    $table->dropColumn('catatan_per_tp');
                }
                if (Schema::hasColumn('grades', 'rppm_id')) {
                    $table->dropConstrainedForeignId('rppm_id');
                }
            });
        }
    }
};
