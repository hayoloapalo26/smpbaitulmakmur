<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat Spatie Role pembina_osis
        $role = Role::firstOrCreate(['name' => 'pembina_osis', 'guard_name' => 'web']);

        // 2. Hubungkan jika ada user dengan jabatan atau nama pembina osis
        $pembinaUsers = User::where('jabatan', 'like', '%pembina osis%')
            ->orWhere('jabatan', 'like', '%osis%')
            ->get();

        foreach ($pembinaUsers as $u) {
            if (!$u->hasRole('pembina_osis')) {
                $u->assignRole('pembina_osis');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::where('name', 'pembina_osis')->first();
        if ($role) {
            $role->delete();
        }
    }
};
