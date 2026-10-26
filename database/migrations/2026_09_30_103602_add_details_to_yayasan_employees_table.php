<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yayasan_employees', function (Blueprint $table) {
            $table->string('education')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('join_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('resign_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('yayasan_employees', function (Blueprint $table) {
            $table->dropColumn(['education', 'address', 'phone', 'email', 'join_date', 'is_active', 'resign_date']);
        });
    }
};