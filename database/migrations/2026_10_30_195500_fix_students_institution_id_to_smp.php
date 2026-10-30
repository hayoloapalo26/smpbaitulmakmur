<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'institution_id')) {
            // Pindahkan seluruh data siswa yang terdaftar di Yayasan (ID = 1) atau NULL ke SMP (ID = 2)
            DB::table('students')
                ->where('institution_id', 1)
                ->orWhereNull('institution_id')
                ->update(['institution_id' => 2]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
