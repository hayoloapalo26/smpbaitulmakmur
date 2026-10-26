<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'superadmin']);

        $user = User::firstOrCreate(
            ['email' => 'superadmin@smpi.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
            ]
        );

        if (!$user->hasRole('superadmin')) {
            $user->assignRole($role);
        }
    }
}
