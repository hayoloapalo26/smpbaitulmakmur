<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyRolesLoginSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::all();
        $password = Hash::make('password'); // Universal password

        foreach ($roles as $role) {
            $roleName = $role->name;
            $email = Str::slug($roleName, '_') . '@smpi.com';
            
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Akun Tes ' . ucwords(str_replace('_', ' ', $roleName)),
                    'password' => $password,
                ]
            );

            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
        }
    }
}
