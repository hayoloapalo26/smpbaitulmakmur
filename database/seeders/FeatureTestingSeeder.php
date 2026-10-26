<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\WorkProgram;
use App\Models\ProgramKurikulum;
use App\Models\ProgramKesiswaan;
use App\Models\HumasProgram;
use App\Models\BkWorkProgram;
use App\Models\TeacherLeaveRequest;
use App\Models\TeachingMaterial;
use App\Models\TeacherSupervision;
use App\Models\Subject;
use Carbon\Carbon;

class FeatureTestingSeeder extends Seeder
{
    public function run(): void
    {
        $wakaKurikulum = User::role('waka_kurikulum')->first() ?? User::find(3);
        $kepalaSekolah = User::role('kepala_sekolah')->first() ?? User::find(2);
        $guru = User::role('guru_mapel')->first() ?? User::find(6);

        // 1. Data Pilar Utama
        \App\Models\ProgramKepsek::create([
            'user_id' => $kepalaSekolah->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Evaluasi Kinerja Guru Semester Ganjil',
            'kategori' => 'Manajemen',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Belum',
            'tujuan' => 'Dummy Data untuk testing akses fitur Kepsek.',
        ]);

        ProgramKurikulum::create([
            'user_id' => $wakaKurikulum->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Penyusunan Jadwal Ujian Akhir',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
            'tujuan' => 'Data simulasi tujuan kurikulum',
        ]);

        $wakaKesiswaan = User::role('waka_kesiswaan')->first() ?? $wakaKurikulum;

        ProgramKesiswaan::create([
            'user_id' => $wakaKesiswaan->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Latihan Dasar Kepemimpinan (LDKS)',
            'kategori' => 'Kesiswaan',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Belum',
            'tujuan' => 'Data simulasi tujuan kesiswaan',
        ]);

        \App\Models\ProgramHumas::create([
            'user_id' => User::role('humas')->first()->id ?? $guru->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Kunjungan Studi Banding Sekolah Mitra',
            'kategori' => 'Kemitraan',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Belum',
            'tujuan' => 'Meningkatkan kerjasama antar sekolah',
        ]);

        \App\Models\ProgramSarpras::create([
            'user_id' => User::role('sarpras')->first()->id ?? $guru->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Pengadaan Proyektor Kelas',
            'kategori' => 'Pengadaan',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Sedang Berjalan',
            'tujuan' => 'Mendukung digitalisasi KBM',
        ]);

        BkWorkProgram::create([
            'user_id' => $guru->id,
            'tahun_ajaran' => '2026/2027',
            'judul_program' => 'Konseling Karir Kelas 9',
            'bidang_layanan' => 'Karir',
            'tipe_pelaksanaan' => 'Mingguan',
            'sasaran' => 'Siswa Kelas 9',
            'waktu_pelaksanaan' => 'Juli - Agustus',
            'tujuan' => 'Membantu siswa menentukan pilihan sekolah lanjutan',
            'status' => 'Sedang Berjalan',
        ]);

        // 2. Data Pengajuan Perizinan (Untuk Testing Approval)
        // A. Cuma Diajukan (Menunggu Waka)
        TeacherLeaveRequest::create([
            'teacher_id' => $guru->id,
            'tanggal_mulai' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'alasan' => 'Ada keperluan keluarga mendadak (Testing Waka Approve)',
            'status' => 'diajukan',
        ]);

        // B. Sudah Disetujui Waka, Menunggu Kepsek
        TeacherLeaveRequest::create([
            'teacher_id' => $guru->id,
            'tanggal_mulai' => Carbon::now()->addDays(3)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(4)->format('Y-m-d'),
            'alasan' => 'Pelatihan MGMP tingkat kota (Testing Kepsek Approve)',
            'status' => 'disetujui_wakakur',
            'substitute_teacher_id' => $wakaKurikulum->id, // Diinval Waka
            'wakakur_approved_by' => $wakaKurikulum->id,
            'wakakur_approved_at' => Carbon::now(),
        ]);

        // 3. Data Perangkat Ajar (Untuk Testing Approval)
        $subject = Subject::first();
        if ($subject) {
            // A. Diajukan
            TeachingMaterial::create([
                'teacher_id' => $guru->id,
                'subject_id' => $subject->id,
                'google_docs_link' => 'https://docs.google.com/document/d/dummy-1',
                'status' => 'diajukan',
            ]);

            // B. Menunggu Kepsek
            TeachingMaterial::create([
                'teacher_id' => $guru->id,
                'subject_id' => $subject->id,
                'google_docs_link' => 'https://docs.google.com/document/d/dummy-2',
                'status' => 'direview_wakakur',
                'wakakur_note' => 'Modul ini sudah bagus, sesuai CP terbaru.',
                'wakakur_approved_by' => $wakaKurikulum->id,
                'wakakur_approved_at' => Carbon::now(),
            ]);
        }

        // 4. Data Supervisi Guru
        TeacherSupervision::create([
            'teacher_id' => $guru->id,
            'supervisor_id' => $kepalaSekolah->id,
            'tanggal' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'hasil' => 'Penyampaian materi sangat interaktif, namun manajemen waktu saat diskusi perlu ditingkatkan.',
            'catatan' => 'Saran: gunakan timer saat memberikan tugas kelompok.',
        ]);

        echo "Dummy Data untuk testing integrasi KS berhasil dimasukkan!\n";
    }
}
