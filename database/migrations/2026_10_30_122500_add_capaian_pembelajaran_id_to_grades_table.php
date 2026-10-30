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
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (!Schema::hasColumn('grades', 'capaian_pembelajaran_id')) {
                    $table->foreignId('capaian_pembelajaran_id')
                        ->nullable()
                        ->after('subject_id')
                        ->constrained('capaian_pembelajarans')
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (Schema::hasColumn('grades', 'capaian_pembelajaran_id')) {
                    $table->dropForeign(['capaian_pembelajaran_id']);
                    $table->dropColumn('capaian_pembelajaran_id');
                }
            });
        }
    }
};
