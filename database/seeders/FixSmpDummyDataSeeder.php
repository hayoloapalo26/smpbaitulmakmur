<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkProgram;
use App\Models\ProgramKurikulum;
use App\Models\ProgramKesiswaan;
use App\Models\HumasProgram;
use App\Models\BkWorkProgram;
use App\Models\User;
use App\Models\SchoolPillarTask;
use App\Models\SchoolPillar;

class FixSmpDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure School Pillars are there
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'SchoolPillarSeeder', '--force' => true]);

        // Catatan: Seluruh data program kerja kurikulum, kesiswaan, humas, bk, dan kepala sekolah
        // dibiarkan pure kosong agar diinput langsung oleh masing-masing guru / penanggung jawab.
    }
}
