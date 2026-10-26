<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_cases', function (Blueprint $table) {
            $table->timestamp('dikembalikan_dari_bk_at')->nullable()->after('diteruskan_ke_bk_at');
            $table->text('catatan_pengembalian_bk')->nullable()->after('dikembalikan_dari_bk_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_cases', function (Blueprint $table) {
            $table->dropColumn(['dikembalikan_dari_bk_at', 'catatan_pengembalian_bk']);
        });
    }
};
