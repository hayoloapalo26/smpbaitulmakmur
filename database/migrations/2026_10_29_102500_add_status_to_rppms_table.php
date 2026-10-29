<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rppms')) {
            Schema::table('rppms', function (Blueprint $table) {
                if (!Schema::hasColumn('rppms', 'status')) {
                    $table->string('status')->default('draft')->after('selected_tp_ids');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rppms')) {
            Schema::table('rppms', function (Blueprint $table) {
                if (Schema::hasColumn('rppms', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};
