<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'kelas_quran')) {
                    $table->string('kelas_quran', 50)->nullable()->after('classroom_id'); // yanbua, tahsin, tahfidz_dasar, tahfidz_lanjutan
                }
                if (!Schema::hasColumn('students', 'guru_quran_id')) {
                    $table->foreignId('guru_quran_id')->nullable()->after('kelas_quran')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('students', 'tanggal_penempatan_quran')) {
                    $table->date('tanggal_penempatan_quran')->nullable()->after('guru_quran_id');
                }
                if (!Schema::hasColumn('students', 'catatan_penempatan_quran')) {
                    $table->text('catatan_penempatan_quran')->nullable()->after('tanggal_penempatan_quran');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'catatan_penempatan_quran')) {
                    $table->dropColumn('catatan_penempatan_quran');
                }
                if (Schema::hasColumn('students', 'tanggal_penempatan_quran')) {
                    $table->dropColumn('tanggal_penempatan_quran');
                }
                if (Schema::hasColumn('students', 'guru_quran_id')) {
                    $table->dropConstrainedForeignId('guru_quran_id');
                }
                if (Schema::hasColumn('students', 'kelas_quran')) {
                    $table->dropColumn('kelas_quran');
                }
            });
        }
    }
};
