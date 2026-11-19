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
        if (Schema::hasTable('daily_attendances') && Schema::hasColumn('daily_attendances', 'late_penalty_points')) {
            try {
                Schema::table('daily_attendances', function (Blueprint $table) {
                    $table->integer('late_penalty_points')->nullable()->default(0)->change();
                });
            } catch (\Throwable $e) {
                // Abaikan jika database engine tidak memerlukan alter table change
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('daily_attendances') && Schema::hasColumn('daily_attendances', 'late_penalty_points')) {
            try {
                Schema::table('daily_attendances', function (Blueprint $table) {
                    $table->integer('late_penalty_points')->default(5)->change();
                });
            } catch (\Throwable $e) {
                //
            }
        }
    }
};
