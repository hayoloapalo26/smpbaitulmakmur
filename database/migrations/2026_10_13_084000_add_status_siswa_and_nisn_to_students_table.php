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
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'nisn')) {
                $table->string('nisn')->nullable()->after('nis');
            }
            if (!Schema::hasColumn('students', 'status_siswa')) {
                $table->string('status_siswa')->default('aktif')->after('classroom_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'status_siswa')) {
                $table->dropColumn('status_siswa');
            }
            if (Schema::hasColumn('students', 'nisn')) {
                $table->dropColumn('nisn');
            }
        });
    }
};
