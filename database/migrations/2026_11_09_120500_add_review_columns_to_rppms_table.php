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
                if (!Schema::hasColumn('rppms', 'catatan_kepala_sekolah')) {
                    $table->text('catatan_kepala_sekolah')->nullable()->after('status');
                }
                if (!Schema::hasColumn('rppms', 'reviewed_by')) {
                    $table->foreignId('reviewed_by')->nullable()->after('catatan_kepala_sekolah')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('rppms', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rppms')) {
            Schema::table('rppms', function (Blueprint $table) {
                if (Schema::hasColumn('rppms', 'reviewed_by')) {
                    $table->dropConstrainedForeignId('reviewed_by');
                }
                if (Schema::hasColumn('rppms', 'reviewed_at')) {
                    $table->dropColumn('reviewed_at');
                }
                if (Schema::hasColumn('rppms', 'catatan_kepala_sekolah')) {
                    $table->dropColumn('catatan_kepala_sekolah');
                }
            });
        }
    }
};
