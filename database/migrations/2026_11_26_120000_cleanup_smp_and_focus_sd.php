<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membersihkan akun percobaan SMP jika sempat terbuat, memastikan data SMP 100% murni, asli, dan tidak tersentuh.
     */
    public function up(): void
    {
        // Hapus akun percobaan SMP agar data SMP tetap 100% steril dan murni
        $testSmpEmails = [
            'kepsek_smp@baitulmakmur.sch.id',
            'kurikulum_smp@baitulmakmur.sch.id',
            'kesiswaan_smp@baitulmakmur.sch.id',
            'tu_smp@baitulmakmur.sch.id',
            'guru_smp@baitulmakmur.sch.id',
            'walikelas_smp@baitulmakmur.sch.id',
            'siswa_smp@baitulmakmur.sch.id',
            'ortu_smp@baitulmakmur.sch.id',
            'yayasan@baitulmakmur.sch.id',
            'ketuayayasan@baitulmakmur.sch.id',
            'penjaminmutu@baitulmakmur.sch.id',
            'keuangan_yayasan@baitulmakmur.sch.id',
            'superadmin@baitulmakmur.sch.id',
        ];

        $users = User::withoutGlobalScopes()->whereIn('email', $testSmpEmails)->get();
        foreach ($users as $u) {
            if (\Illuminate\Support\Facades\Schema::hasTable('institution_user')) {
                DB::table('institution_user')->where('user_id', $u->id)->delete();
            }
            $u->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
