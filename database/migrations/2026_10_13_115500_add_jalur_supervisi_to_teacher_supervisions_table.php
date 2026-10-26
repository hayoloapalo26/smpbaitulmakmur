<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_supervisions', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_supervisions', 'jalur_supervisi')) {
                $table->string('jalur_supervisi')->default('pengajuan_guru')->after('catatan');
            }
            $table->unsignedBigInteger('supervisor_id')->nullable()->change();
            $table->text('hasil')->nullable()->change();
            $table->text('catatan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_supervisions', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_supervisions', 'jalur_supervisi')) {
                $table->dropColumn('jalur_supervisi');
            }
        });
    }
};
