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
        Schema::table('program_humas', function (Blueprint $table) {
            $table->string('status_persetujuan')->default('Menunggu Persetujuan')->after('status_pelaksanaan');
            $table->text('catatan_kepsek')->nullable()->after('status_persetujuan');
            $table->dateTime('tanggal_persetujuan')->nullable()->after('catatan_kepsek');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('tanggal_persetujuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_humas', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status_persetujuan', 'catatan_kepsek', 'tanggal_persetujuan', 'approved_by']);
        });
    }
};
