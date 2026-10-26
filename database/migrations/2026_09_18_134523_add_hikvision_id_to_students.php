<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // ID yang didaftarkan di perangkat Hikvision (Employee No)
            // Bisa berupa NIS langsung atau ID custom
            $table->string('hikvision_employee_no')->nullable()->unique()->after('nis');
            $table->string('nfc_card_no')->nullable()->unique()->after('hikvision_employee_no');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['hikvision_employee_no', 'nfc_card_no']);
        });
    }
};
