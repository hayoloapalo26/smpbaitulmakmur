<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Classroom;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Dapatkan ID kelas 7, 8, dan 9 untuk SMP Islam Baitul Makmur (institution_id = 2)
        $k8 = Classroom::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('institution_id', 2)->orWhere('institution_id', 1)->orWhereNull('institution_id');
            })
            ->where(function ($q) {
                $q->where('tingkat', '8')->orWhere('name', 'like', '%8%')->orWhere('name', 'like', '%VIII%');
            })
            ->first();

        $k7 = Classroom::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('institution_id', 2)->orWhere('institution_id', 1)->orWhereNull('institution_id');
            })
            ->where(function ($q) {
                $q->where('tingkat', '7')->orWhere('name', 'like', '%7%')->orWhere('name', 'like', '%VII%');
            })
            ->first();

        $k9 = Classroom::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('institution_id', 2)->orWhere('institution_id', 1)->orWhereNull('institution_id');
            })
            ->where(function ($q) {
                $q->where('tingkat', '9')->orWhere('name', 'like', '%9%')->orWhere('name', 'like', '%IX%');
            })
            ->first();

        // 2. Daftar NIS resmi untuk masing-masing kelas
        $k8Nis = ['0285','0286','0287','0288','0289','0290','0291','0293','0294','0295','0296','0297','0298','0299','0300','0301','0302','0304','0305','0306','0307','0308','0309','0310','0311','0312','0313','0314','0315','0319'];
        $k7Nis = ['0320','0321','0322','0323','0324','0325','0326','0327','0328','0329','0330','0331','0332','0333','0334','0335','0336','0337','0338','0339','0340','0341','0342','0343','0344','0345','0346','0347','0348','0349'];
        $k9Nis = ['0262','0282','0264','0265','0266','0268','0269','0280','0281','0270','0271','0272','0274','0275','0276','0277','0316','0317','0318'];

        if ($k8) {
            DB::table('students')
                ->whereNull('classroom_id')
                ->whereIn('nis', $k8Nis)
                ->update(['classroom_id' => $k8->id, 'updated_at' => now()]);
        }

        if ($k7) {
            DB::table('students')
                ->whereNull('classroom_id')
                ->whereIn('nis', $k7Nis)
                ->update(['classroom_id' => $k7->id, 'updated_at' => now()]);
        }

        if ($k9) {
            DB::table('students')
                ->whereNull('classroom_id')
                ->whereIn('nis', $k9Nis)
                ->update(['classroom_id' => $k9->id, 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }
};
