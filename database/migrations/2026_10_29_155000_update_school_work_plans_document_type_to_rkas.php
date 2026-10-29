<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_work_plans')) {
            Schema::table('school_work_plans', function (Blueprint $table) {
                $table->string('document_type', 50)->change();
            });

            // Update existing 'RKS' records to 'RKAS'
            DB::table('school_work_plans')
                ->where('document_type', 'RKS')
                ->update(['document_type' => 'RKAS']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('school_work_plans')) {
            DB::table('school_work_plans')
                ->where('document_type', 'RKAS')
                ->update(['document_type' => 'RKS']);
        }
    }
};
