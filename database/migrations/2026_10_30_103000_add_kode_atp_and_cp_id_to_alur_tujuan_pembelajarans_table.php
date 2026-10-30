<?php

use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\TujuanPembelajaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('alur_tujuan_pembelajarans')) {
            Schema::table('alur_tujuan_pembelajarans', function (Blueprint $table) {
                if (!Schema::hasColumn('alur_tujuan_pembelajarans', 'kode_atp')) {
                    $table->string('kode_atp')->nullable()->after('subject_id');
                }
                if (!Schema::hasColumn('alur_tujuan_pembelajarans', 'capaian_pembelajaran_id')) {
                    $table->foreignId('capaian_pembelajaran_id')->nullable()->after('kode_atp')->constrained('capaian_pembelajarans')->nullOnDelete();
                }
            });

            // Backfill: untuk setiap CP, pastikan ada ATP spesifik per CP dengan kode_atp dan kode_cp
            $cps = CapaianPembelajaran::all();
            foreach ($cps as $cp) {
                if ($cp->guru_id && $cp->subject_id) {
                    AlurTujuanPembelajaran::syncForTeacherAndSubject(
                        $cp->guru_id,
                        $cp->subject_id,
                        $cp->tingkat,
                        $cp->tahun_ajaran ?? '2026/2027',
                        $cp->semester ?? 'Ganjil',
                        $cp->id
                    );
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('alur_tujuan_pembelajarans')) {
            Schema::table('alur_tujuan_pembelajarans', function (Blueprint $table) {
                if (Schema::hasColumn('alur_tujuan_pembelajarans', 'capaian_pembelajaran_id')) {
                    $table->dropForeign(['capaian_pembelajaran_id']);
                    $table->dropColumn('capaian_pembelajaran_id');
                }
                if (Schema::hasColumn('alur_tujuan_pembelajarans', 'kode_atp')) {
                    $table->dropColumn('kode_atp');
                }
            });
        }
    }
};
