<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_cases', function (Blueprint $table) {
            $table->json('kategori_kasus')->nullable()->after('wali_kelas_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_cases', function (Blueprint $table) {
            $table->dropColumn('kategori_kasus');
        });
    }
};
