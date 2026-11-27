<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\GuardianService;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membersihkan dan menggabungkan akun & data orang tua duplikat (termasuk Evi Ernawati).
     */
    public function up(): void
    {
        GuardianService::deduplicateAll();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reverse
    }
};
