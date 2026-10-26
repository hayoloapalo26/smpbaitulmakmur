<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pastikan role guru_piket ada
        $role = Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);

        // 2. Cari apakah ada user Guru Piket atau user dummy piket sebelumnya (Guru Siti)
        $piketUser = User::withoutGlobalScopes()->where('name', 'Guru Piket')->first();

        if (!$piketUser) {
            $siti = User::withoutGlobalScopes()->where('email', 'gurusiti@smp.com')->orWhere('name', 'like', '%Guru Siti%')->first();
            $instId = 2;
            if (!\App\Models\Institution::where('id', 2)->exists()) {
                $instId = \App\Models\Institution::first()?->id;
            }

            if ($siti) {
                $siti->update([
                    'name' => 'Guru Piket',
                    'username' => 'gurupiket',
                    'email' => 'gurupiket@smp.com',
                ]);
                $piketUser = $siti;
            } else {
                $piketUser = User::withoutGlobalScopes()->create([
                    'name' => 'Guru Piket',
                    'username' => 'gurupiket',
                    'email' => 'gurupiket@smp.com',
                    'password' => Hash::make('password123'),
                    'institution_id' => $instId,
                    'is_active' => true,
                ]);
            }
        }

        if ($piketUser && !$piketUser->hasRole('guru_piket')) {
            $piketUser->assignRole('guru_piket');
        }
    }

    public function down(): void
    {
        // Tidak perlu menghapus data user
    }
};
