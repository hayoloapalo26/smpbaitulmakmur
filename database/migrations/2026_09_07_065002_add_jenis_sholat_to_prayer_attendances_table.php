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
        Schema::table('prayer_attendances', function (Blueprint $table) {
            $table->enum('jenis_sholat', ['Dhuha', 'Dhuhur', 'Ashar'])->default('Dhuhur')->after('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prayer_attendances', function (Blueprint $table) {
            $table->dropColumn('jenis_sholat');
        });
    }
};
