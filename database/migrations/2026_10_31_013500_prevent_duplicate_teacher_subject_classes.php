<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('teacher_subject_classes')) {
            // 1. Bersihkan duplikat yang sudah ada di database sebelum membuat unique index
            // Mempertahankan ID terkecil dan menghapus entri dobel
            $duplicates = DB::table('teacher_subject_classes')
                ->select('subject_id', 'classroom_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
                ->groupBy('subject_id', 'classroom_id')
                ->having('total', '>', 1)
                ->get();

            foreach ($duplicates as $dup) {
                DB::table('teacher_subject_classes')
                    ->where('subject_id', $dup->subject_id)
                    ->where('classroom_id', $dup->classroom_id)
                    ->where('id', '!=', $dup->keep_id)
                    ->delete();
            }

            // 2. Tambahkan unique constraint pada pasangan subject_id dan classroom_id
            Schema::table('teacher_subject_classes', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('teacher_subject_classes'))->pluck('name')->toArray();
                if (!in_array('tsc_subject_classroom_unique', $indexes)) {
                    $table->unique(['subject_id', 'classroom_id'], 'tsc_subject_classroom_unique');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teacher_subject_classes')) {
            Schema::table('teacher_subject_classes', function (Blueprint $table) {
                try {
                    $table->dropUnique('tsc_subject_classroom_unique');
                } catch (\Throwable $e) {
                    // Abaikan jika index tidak ditemukan
                }
            });
        }
    }
};
