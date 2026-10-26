<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Memastikan tidak ada kendala foreign key saat seeding (jika memakai truncate/fresh nantinya)
        Schema::disableForeignKeyConstraints();

        $this->call([
            // 1. Master Data Hierarki
            InstitutionUserSeeder::class,
            
            // 2. Data Dashboard Yayasan
            YayasanProgressSeeder::class,
            
            // 3. Data Dashboard SMP
            SmpProgressSeeder::class,
        ]);

        Schema::enableForeignKeyConstraints();
    }
}
