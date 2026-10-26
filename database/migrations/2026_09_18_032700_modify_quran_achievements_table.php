<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_achievements', function (Blueprint $table) {
            // Change enum to string so we can store json arrays
            $table->string('type')->nullable()->change();
            $table->text('catatan')->nullable()->after('ayah');
        });
    }

    public function down(): void
    {
        Schema::table('quran_achievements', function (Blueprint $table) {
            $table->dropColumn('catatan');
            // Reverting type to enum might fail if array data exists, so we leave it as string on rollback or do nothing
        });
    }
};
