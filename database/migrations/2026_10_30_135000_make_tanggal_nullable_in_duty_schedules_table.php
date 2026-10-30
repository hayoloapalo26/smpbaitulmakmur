<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('duty_schedules', function (Blueprint $table) {
            $table->date('tanggal')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('duty_schedules', function (Blueprint $table) {
            $table->date('tanggal')->nullable(false)->change();
        });
    }
};
