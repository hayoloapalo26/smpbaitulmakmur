<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (!Schema::hasColumn('teaching_journals', 'is_kegiatan_lain')) {
                    $table->boolean('is_kegiatan_lain')->default(false)->after('materi');
                }
                if (!Schema::hasColumn('teaching_journals', 'nama_kegiatan_lain')) {
                    $table->string('nama_kegiatan_lain')->nullable()->after('is_kegiatan_lain');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (Schema::hasColumn('teaching_journals', 'nama_kegiatan_lain')) {
                    $table->dropColumn('nama_kegiatan_lain');
                }
                if (Schema::hasColumn('teaching_journals', 'is_kegiatan_lain')) {
                    $table->dropColumn('is_kegiatan_lain');
                }
            });
        }
    }
};
