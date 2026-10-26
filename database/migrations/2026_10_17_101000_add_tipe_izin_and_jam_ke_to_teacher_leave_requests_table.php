<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_leave_requests', function (Blueprint $table) {
            $table->string('tipe_izin')->default('harian')->after('teacher_id'); // 'harian', 'jam_tertentu'
            $table->json('jam_ke')->nullable()->after('tipe_izin');
            $table->integer('jam_ke_mulai')->nullable()->after('jam_ke');
            $table->integer('jam_ke_selesai')->nullable()->after('jam_ke_mulai');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_leave_requests', function (Blueprint $table) {
            $table->dropColumn(['tipe_izin', 'jam_ke', 'jam_ke_mulai', 'jam_ke_selesai']);
        });
    }
};
