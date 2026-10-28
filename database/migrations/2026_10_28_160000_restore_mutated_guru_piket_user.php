<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cari user yang memiliki role waka_kurikulum / guru_mapel tetapi namanya tertulis 'Guru Piket'
        $mutatedUsers = User::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('name', 'like', '%Guru Piket%')
                  ->orWhere('username', 'gurupiket')
                  ->orWhere('email', 'gurupiket@smp.com');
            })
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['waka_kurikulum', 'guru_mapel']))
                  ->orWhere('email', 'like', '%wakakur%')
                  ->orWhere('email', 'like', '%siti%');
            })
            ->get();

        foreach ($mutatedUsers as $u) {
            // Pulihkan nama asli dan username Waka Kurikulum
            $u->name = 'Siti Aminah, M.Pd';
            $u->username = 'wakakur';
            if ($u->email === 'gurupiket@smp.com') {
                // Pastikan email unik jika wakakur@gmail.com sudah ada
                $existing = User::withoutGlobalScopes()->where('email', 'wakakur@gmail.com')->where('id', '!=', $u->id)->first();
                $u->email = $existing ? 'siti.aminah@smp.com' : 'wakakur@gmail.com';
            }
            $u->save();

            // Cabut role guru_piket dari akun riil Waka Kurikulum
            if (method_exists($u, 'removeRole') && $u->hasRole('guru_piket')) {
                $u->removeRole('guru_piket');
            }
        }

        // 2. Buat akun sistem terisolasi khusus Inval Piket
        User::ensureGuruPiketUser();
    }

    public function down(): void
    {
    }
};
