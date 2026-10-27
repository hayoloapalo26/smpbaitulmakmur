<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->string('status', 50)->default('masuk')->change();
        });

        // Selaraskan data status 'hadir' lama menjadi 'masuk'
        DB::table('teacher_attendances')
            ->where('status', 'hadir')
            ->update(['status' => 'masuk']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('teacher_attendances')
            ->where('status', 'masuk')
            ->update(['status' => 'hadir']);
    }
};
