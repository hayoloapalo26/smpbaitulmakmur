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
        $userId = User::where('institution_id', 2)->first()->id ?? 1;

        // Ensure School Pillars are there
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'SchoolPillarSeeder', '--force' => true]);

        // 1. Work Programs (Pilar 1 - Kepala Sekolah)
        for ($i = 1; $i <= 4; $i++) {
            WorkProgram::firstOrCreate(
                ['nama_program' => 'Supervisi Akademik & Administrasi ' . $i],
                [
                    'divisi' => 'kepala_sekolah',
                    'penanggung_jawab_id' => $userId,
                    'tanggal_mulai' => now()->subDays(10),
                    'tanggal_selesai' => now()->addDays(30),
                    'status_pelaksanaan' => $i <= 3 ? 'selesai' : 'berjalan',
                ]
            );
        }

        // 2. Program Kurikulum (Pilar 3 - Kurikulum)
        for ($i = 1; $i <= 4; $i++) {
            ProgramKurikulum::firstOrCreate(
                ['nama_program' => 'Pengembangan Modul Ajar Digital ' . $i],
                [
                    'user_id' => $userId,
                    'tahun_ajaran' => '2026/2027',
                    'kategori' => 'Akademik',
                    'tipe_pelaksanaan' => 'Bulanan',
                    'sifat' => 'Wajib',
                    'status_pelaksanaan' => $i <= 2 ? 'Selesai' : 'Rencana',
                ]
            );
        }

        // 3. Program Kesiswaan (Pilar 5 - Kesiswaan)
        for ($i = 1; $i <= 3; $i++) {
            ProgramKesiswaan::firstOrCreate(
                ['nama_program' => 'Latihan Dasar Kepemimpinan (LDKS) ' . $i],
                [
                    'user_id' => $userId,
                    'tahun_ajaran' => '2026/2027',
                    'kategori' => 'OSIS',
                    'tipe_pelaksanaan' => 'Tahunan',
                    'sifat' => 'Wajib',
                    'status_pelaksanaan' => $i == 1 ? 'Selesai' : 'Rencana',
                ]
            );
        }

        // 4. Humas Program (Pilar 8 - Humas)
        for ($i = 1; $i <= 3; $i++) {
            HumasProgram::firstOrCreate(
                ['nama' => 'Rapat Wali Murid Rutin ' . $i],
                [
                    'status' => $i <= 2 ? 'sudah' : 'belum',
                    'tanggal' => now()->subDays(5),
                ]
            );
        }

        // 5. BK Work Program (Pilar 9 - BK)
        for ($i = 1; $i <= 3; $i++) {
            BkWorkProgram::firstOrCreate(
                ['judul_program' => 'Asesmen Minat & Bakat Siswa ' . $i],
                [
                    'user_id' => $userId,
                    'bidang_layanan' => 'Karir',
                    'sasaran' => 'Kelas 7 & 8',
                    'waktu_pelaksanaan' => 'Semester Ganjil',
                    'tujuan' => 'Pemetaan minat bakat',
                    'status' => $i == 1 ? 'Terlaksana' : 'Rencana',
                ]
            );
        }
    }
}
