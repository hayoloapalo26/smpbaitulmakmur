<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkProgram;
use App\Models\ProgramKurikulum;
use App\Models\ProgramKesiswaan;
use App\Models\HumasProgram;
use App\Models\BkWorkProgram;
use App\Models\User;

class YayasanDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::first()->id ?? 1;

        // 1. Work Programs
        for ($i = 1; $i <= 5; $i++) {
            WorkProgram::create([
                'nama_program' => 'Program Kerja ' . $i,
                'divisi' => 'kepala_sekolah',
                'penanggung_jawab_id' => $userId,
                'tanggal_mulai' => now(),
                'tanggal_selesai' => now()->addDays(30),
                'status_pelaksanaan' => 'selesai',
            ]);
        }

        // 3. Program Kurikulum
        for ($i = 1; $i <= 5; $i++) {
            ProgramKurikulum::create([
                'nama_program' => 'Program Kurikulum ' . $i,
                'jenis_program' => 'Intrakurikuler',
                'penanggung_jawab_id' => $userId,
                'tanggal_pelaksanaan' => now(),
                'status_pelaksanaan' => 'Selesai',
            ]);
        }

        // 5. Program Kesiswaan
        for ($i = 1; $i <= 5; $i++) {
            ProgramKesiswaan::create([
                'nama_program' => 'Program Kesiswaan ' . $i,
                'jenis_kegiatan' => 'OSIS',
                'penanggung_jawab_id' => $userId,
                'tanggal_pelaksanaan' => now(),
                'status_pelaksanaan' => 'Selesai',
            ]);
        }

        // 8. Humas Program
        for ($i = 1; $i <= 5; $i++) {
            HumasProgram::create([
                'judul_kegiatan' => 'Program Humas ' . $i,
                'jenis_kegiatan' => 'Internal',
                'tanggal_pelaksanaan' => now(),
                'status' => 'sudah',
            ]);
        }

        // 9. BK Work Program
        for ($i = 1; $i <= 5; $i++) {
            BkWorkProgram::create([
                'jenis_layanan' => 'Pribadi',
                'judul_materi' => 'Program BK ' . $i,
                'tanggal_pelaksanaan' => now(),
                'status' => 'Terlaksana',
            ]);
        }
    }
}
