<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SchoolPillar;
use App\Models\SchoolPillarTask;

class SchoolPillarSeeder extends Seeder
{
    public function run(): void
    {
        $pillarsData = [
            [
                'name' => 'Bidang Manajemen',
                'order' => 1,
                'tasks' => [
                    ['code' => '1.1.a', 'name' => 'Penyusunan rencana program sekolah', 'role' => 'KS, WKr, WKs, SP, Hm, BK'],
                    ['code' => '1.1.b', 'name' => 'Pelaksanaan rencana program sekolah', 'role' => 'KS, WKr, WKs, SP, Hm, BK'],
                    ['code' => '1.1.c', 'name' => 'Monitoring dan pengendalian pelaksanaan program sekolah', 'role' => 'KS'],
                    ['code' => '1.1.d', 'name' => 'Penilaian / evaluasi program dan laporan', 'role' => 'KS'],
                    ['code' => '1.2.a', 'name' => 'Pembuatan administrasi sekolah', 'role' => 'TU'],
                    ['code' => '1.2.b', 'name' => 'Pendistribusian administrasi sekolah', 'role' => 'TU'],
                    ['code' => '1.2.c', 'name' => 'Pengarsipan administrasi sekolah', 'role' => 'TU'],
                    ['code' => '1.3', 'name' => 'Pengelolaan organisasi sekolah', 'role' => 'KS'],
                    ['code' => '1.4', 'name' => 'Penetapan SOP kerja organisasi sekolah', 'role' => 'KS'],
                    ['code' => '1.5', 'name' => 'Penilaian mutu sekolah', 'role' => 'KS'],
                    ['code' => '1.6', 'name' => 'Akreditasi Sekolah', 'role' => 'KS'],
                    ['code' => '1.7', 'name' => 'SOP terkait', 'role' => 'KS', 'red' => true],
                ]
            ],
            [
                'name' => 'Mutu Lulusan',
                'order' => 2,
                'tasks' => [
                    ['code' => '2.1', 'name' => 'Profil atau mutu lulusan siswa', 'role' => 'WKr'],
                    ['code' => '2.2', 'name' => 'Penilaian ketercapaian profil lulusan', 'role' => 'WKr'],
                    ['code' => '2.3', 'name' => 'SOP penilaian profil atau mutu lulusan', 'role' => 'KS', 'red' => true],
                ]
            ],
            [
                'name' => 'Bidang Kurikulum dan Pembelajaran',
                'order' => 3,
                'tasks' => [
                    ['code' => '3.1', 'name' => 'Analisis pengembangan kurikulum', 'role' => 'WKr'],
                    ['code' => '3.2', 'name' => 'Penyusunan rencana kerja kurikulum dan pembelajaran', 'role' => 'WKr'],
                    ['code' => '3.3', 'name' => 'Administrasi kurikulum dan pembelajaran', 'role' => 'WKr'],
                    ['code' => '3.4', 'name' => 'Pelaksanaan kurikulum dan pembelajaran', 'role' => 'WKr'],
                    ['code' => '3.5', 'name' => 'Monitoring dan supervisi pembelajaran', 'role' => 'WKr'],
                    ['code' => '3.6', 'name' => 'Penilaian dan laporan pembelajaran', 'role' => 'WKr'],
                    ['code' => '3.7', 'name' => 'Monitoring, pembinaan dan pengendalian poin 3.1 - 3.6', 'role' => 'KS'],
                ]
            ],
            [
                'name' => 'PTK (Pendidik dan Tenaga Kependidikan)',
                'order' => 4,
                'tasks' => [
                    ['code' => '4.1', 'name' => 'Rekrutmen PTK', 'role' => 'KS'],
                    ['code' => '4.2', 'name' => 'Penempatan PTK', 'role' => 'KS'],
                    ['code' => '4.3', 'name' => 'Pembinaan dan peningkatan PTK', 'role' => 'KS'],
                    ['code' => '4.4', 'name' => 'Pemeliharaan dan penghargaan PTK', 'role' => 'KS'],
                    ['code' => '4.5', 'name' => 'Monitoring dan penilaian PTK', 'role' => 'KS'],
                    ['code' => '4.6', 'name' => 'Pemutusan PTK', 'role' => 'KS'],
                    ['code' => '4.7', 'name' => 'SOP terkait', 'role' => 'KS', 'red' => true],
                    ['code' => '4.8', 'name' => 'Monitoring, pembinaan dan pengendalian', 'role' => 'KS'],
                ]
            ],
            [
                'name' => 'Kesiswaan',
                'order' => 5,
                'tasks' => [
                    ['code' => '5.1', 'name' => 'Analisis kebutuhan siswa', 'role' => 'WKs'],
                    ['code' => '5.2', 'name' => 'Penyusunan rencana kerja kesiswaan', 'role' => 'WKs'],
                    ['code' => '5.3', 'name' => 'Penerimaan siswa', 'role' => 'WKs'],
                    ['code' => '5.4', 'name' => 'Orientasi & penempatan siswa', 'role' => 'WKs'],
                    ['code' => '5.5', 'name' => 'Pembinaan dan pengembangan siswa (minat bakat akademik dan non akademik, karakter)', 'role' => 'WKs'],
                    ['code' => '5.6', 'name' => 'Ekstrakurikuler', 'role' => 'WKs'],
                    ['code' => '5.7', 'name' => 'Kelulusan siswa', 'role' => 'WKs'],
                    ['code' => '5.8', 'name' => 'SOP terkait', 'role' => 'KS', 'red' => true],
                    ['code' => '5.9', 'name' => 'Monitoring, pembinaan dan pengendalian', 'role' => 'KS'],
                ]
            ],
            [
                'name' => 'Sarpras (Sarana Prasarana)',
                'order' => 6,
                'tasks' => [
                    ['code' => '6.1', 'name' => 'Analisis kebutuhan Sarpras & lingkungan', 'role' => 'SP'],
                    ['code' => '6.2', 'name' => 'Penyusunan rencana kerja Sarpras', 'role' => 'SP'],
                    ['code' => '6.3', 'name' => 'Pengadaan', 'role' => 'SP'],
                    ['code' => '6.4', 'name' => 'Pendataan dan penempatan', 'role' => 'SP'],
                    ['code' => '6.5', 'name' => 'Penggunaan', 'role' => 'SP'],
                    ['code' => '6.6', 'name' => 'Perawatan', 'role' => 'SP'],
                    ['code' => '6.7', 'name' => 'Penghapusan', 'role' => 'SP'],
                    ['code' => '6.8', 'name' => 'SOP terkait', 'role' => 'KS', 'red' => true],
                    ['code' => '6.9', 'name' => 'Pengajuan Pengadaan ke KS (mempengaruhi stok barang)', 'role' => 'SP'],
                    ['code' => '6.10', 'name' => 'Monitoring, pembinaan dan pengendalian', 'role' => 'KS'],
                ]
            ],
            [
                'name' => 'Keuangan',
                'order' => 7,
                'tasks' => [
                    ['code' => '7.1', 'name' => 'Input data status murid sudah membayar atau belum', 'role' => 'TU'],
                ]
            ],
            [
                'name' => 'Kehumasan',
                'order' => 8,
                'tasks' => [
                    ['code' => '8.1', 'name' => 'Analisis internal dan eksternal sekolah', 'role' => 'Hm'],
                    ['code' => '8.2', 'name' => 'Penyusunan rencana kerja humas (ruang lingkup kerja)', 'role' => 'Hm'],
                    ['code' => '8.3', 'name' => 'Pemeliharaan hubungan internal dan eskternal sekolah', 'role' => 'Hm'],
                    ['code' => '8.4', 'name' => 'Kerjasama sekolah dengan orang tua', 'role' => 'Hm'],
                    ['code' => '8.5', 'name' => 'Kerjasama dengan eksternal sekolah', 'role' => 'Hm'],
                    ['code' => '8.6', 'name' => 'Sistem komunikasi sekolah ke stake holder dan masyarakat', 'role' => 'Hm'],
                    ['code' => '8.7', 'name' => 'Monitoring dan pengendalian program humas', 'role' => 'Hm'],
                    ['code' => '8.8', 'name' => 'Penilaian/evaluasi', 'role' => 'Hm'],
                    ['code' => '8.9', 'name' => 'SOP terkait', 'role' => 'KS', 'red' => true],
                    ['code' => '8.10', 'name' => 'Monitoring, pembinaan dan pengendalian', 'role' => 'KS'],
                ]
            ],
            [
                'name' => 'Layanan Khusus Sekolah',
                'order' => 9,
                'tasks' => [
                    ['code' => '9.1', 'name' => 'Analisis kebutuhan layanan khusus', 'role' => 'BK, TU'],
                    ['code' => '9.2', 'name' => 'Perencanaan rencana kerja Layanan Khusus Sekolah', 'role' => 'BK, TU'],
                    ['code' => '9.3', 'name' => 'Pengembangan layanan khusus sekolah', 'role' => 'BK, TU'],
                    ['code' => '9.4', 'name' => 'Pelaksanaan layanan khusus sekolah', 'role' => 'BK, TU'],
                    ['code' => '9.5', 'name' => 'Monitoring dan pengendalian pelaksanaan', 'role' => 'KS'],
                    ['code' => '9.6', 'name' => 'Penilaian dan evaluasi', 'role' => 'KS'],
                    ['code' => '9.7', 'name' => 'SOP terkait', 'role' => 'KS', 'red' => true],
                    ['code' => '9.8', 'name' => 'Monitoring, pembinaan dan pengendalian', 'role' => 'KS'],
                    ['code' => '9.9.1', 'name' => 'Layanan BK', 'role' => 'BK'],
                    ['code' => '9.9.2', 'name' => 'Layanan Perpustakaan', 'role' => 'SP, TU'],
                    ['code' => '9.9.3', 'name' => 'Layanan Transportasi', 'role' => 'SP'],
                    ['code' => '9.9.4', 'name' => 'Layanan Lingkungan Sekolah (kebersihan, kesehatan, dll)', 'role' => 'SP, WKs'],
                    ['code' => '9.9.5', 'name' => 'Sistem informasi sekolah-orang tua', 'role' => 'Hm'],
                ]
            ],
        ];

        // Seed data
        SchoolPillar::query()->delete(); // Reset for clean seed
        SchoolPillarTask::query()->delete();

        foreach ($pillarsData as $pillarData) {
            $pillar = SchoolPillar::create([
                'name' => $pillarData['name'],
                'order' => $pillarData['order']
            ]);

            $taskOrder = 1;
            foreach ($pillarData['tasks'] as $taskData) {
                SchoolPillarTask::create([
                    'school_pillar_id' => $pillar->id,
                    'code' => $taskData['code'],
                    'name' => $taskData['name'],
                    'assigned_role' => $taskData['role'],
                    'is_sop_merah' => $taskData['red'] ?? false,
                    'order' => $taskOrder++
                ]);
            }
        }
    }
}
