<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\YayasanProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class YayasanProgressSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create 9 Pillars (Programs)
        $pillars = [
            'Pilar 1: Manajemen Kurikulum & Pembelajaran',
            'Pilar 2: Manajemen Kesiswaan',
            'Pilar 3: Manajemen SDM (Guru & Tendik)',
            'Pilar 4: Manajemen Sarana Prasarana',
            'Pilar 5: Manajemen Keuangan & Pembiayaan',
            'Pilar 6: Manajemen Hubungan Masyarakat',
            'Pilar 7: Manajemen Ketenagakerjaan & Tata Usaha',
            'Pilar 8: Manajemen IT & Digitalisasi',
            'Pilar 9: Manajemen Prestasi & Penjaminan Mutu'
        ];

        $programIds = [];
        foreach ($pillars as $idx => $pillar) {
            $program = YayasanProgram::firstOrCreate(
                ['name' => $pillar],
                ['description' => 'Deskripsi program untuk ' . $pillar]
            );
            $programIds[] = $program->id;
        }

        // Attach Pillars to SMP Islam (Institution ID 2) with varied completion statuses
        $smp = Institution::find(2);
        if ($smp) {
            $syncData = [];
            foreach ($programIds as $idx => $pid) {
                // Vary completion randomly, but bias towards some completed to show progress (e.g., 60-80%)
                $isCompleted = ($idx < 6) ? true : (rand(0, 1) == 1);
                $syncData[$pid] = [
                    'is_completed' => $isCompleted,
                    'notes' => $isCompleted ? 'Program terealisasi dengan baik pada bulan ini.' : 'Masih dalam proses pengerjaan.',
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 10))
                ];
            }
            $smp->yayasanPrograms()->syncWithoutDetaching($syncData);
        }
    }
}
