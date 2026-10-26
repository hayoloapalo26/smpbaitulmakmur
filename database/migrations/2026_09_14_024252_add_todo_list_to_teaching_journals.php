<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->json('kegiatan_rutin')->nullable()->after('jam_ke');
            $table->string('materi')->nullable()->change(); // Ubah ke nullable string
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->dropColumn('kegiatan_rutin');
            $table->text('materi')->nullable(false)->change();
        });
    }
};
