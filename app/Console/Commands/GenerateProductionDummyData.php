<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\TeacherTodoList;
use App\Models\WorkProgram;
use App\Models\ProgramKurikulum;
use App\Models\ProgramKesiswaan;
use App\Models\HumasProgram;
use App\Models\BkWorkProgram;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class GenerateProductionDummyData extends Command
{
    protected $signature = 'dummy:generate';
    protected $description = 'Generate production-like dummy data';

    public function handle()
    {
        // Check if Spatie roles are initialized
        try {
            $roles = ['kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'sarpras', 'humas', 'bk', 'tata_usaha', 'guru_mapel', 'guru_alquran'];
            foreach ($roles as $r) {
                Role::findOrCreate($r, 'web');
            }
        } catch (\Exception $e) {
            $this->error('Roles setup error: ' . $e->getMessage());
        }

        // Create Teachers
        $teachersData = [
            ['name' => 'Budi Santoso, M.Pd', 'email' => 'budi@smpi.com', 'role' => 'kepala_sekolah', 'jabatan' => 'Kepala Sekolah'],
            ['name' => 'Siti Aminah, M.Pd', 'email' => 'siti@smpi.com', 'role' => 'waka_kurikulum', 'jabatan' => 'Waka Kurikulum'],
            ['name' => 'Ahmad Fauzi, S.Pd', 'email' => 'ahmad@smpi.com', 'role' => 'waka_kesiswaan', 'jabatan' => 'Waka Kesiswaan'],
            ['name' => 'Dewi Lestari, S.E', 'email' => 'dewi@smpi.com', 'role' => 'tata_usaha', 'jabatan' => 'Tata Usaha'],
            ['name' => 'Rina Mulyani, S.Psi', 'email' => 'rina@smpi.com', 'role' => 'bk', 'jabatan' => 'Guru BK'],
            ['name' => 'Andi Pratama, S.Kom', 'email' => 'andi@smpi.com', 'role' => 'sarpras', 'jabatan' => 'Sarpras'],
            ['name' => 'Lina Marlina, S.I.Kom', 'email' => 'lina@smpi.com', 'role' => 'humas', 'jabatan' => 'Humas'],
            ['name' => 'Eko Prasetyo, S.Pd', 'email' => 'eko@smpi.com', 'role' => 'guru_mapel', 'jabatan' => 'Guru Mapel'],
            ['name' => 'Bambang Pamungkas, S.Pd', 'email' => 'bambang@smpi.com', 'role' => 'guru_mapel', 'jabatan' => 'Guru Mapel'],
            ['name' => 'Sri Wahyuni, S.Ag', 'email' => 'sri@smpi.com', 'role' => 'guru_alquran', 'jabatan' => 'Guru Al-Quran'],
            ['name' => 'Rio Haryanto, S.Pd', 'email' => 'rio@smpi.com', 'role' => 'guru_mapel', 'jabatan' => 'Guru Mapel'],
            ['name' => 'Linda Kumalasari, S.Psi', 'email' => 'linda@smpi.com', 'role' => 'bk', 'jabatan' => 'Guru BK'],
        ];

        $teacherIds = [];

        foreach ($teachersData as $td) {
            $user = User::where('email', $td['email'])->first();
            if (!$user) {
                $user = new User();
                $user->name = $td['name'];
                $user->email = $td['email'];
                $user->password = Hash::make('password123');
            }
            // Update profile info
            $user->no_induk_yayasan = '198' . rand(1000000, 9999999);
            $user->nuptk = (string) rand(10000000, 99999999);
            $user->tempat_lahir = 'Jakarta';
            $user->tanggal_lahir = Carbon::now()->subYears(rand(25, 50));
            $user->gender = rand(0, 1) ? 'L' : 'P';
            $user->jenjang_pendidikan = 'S1';
            $user->status_kepegawaian = 'GTY';
            $user->jabatan = $td['jabatan'];
            $user->save();
            
            if (!$user->hasRole($td['role'])) {
                $user->assignRole($td['role']);
            }
            $teacherIds[] = $user->id;
        }

        // Generate Todo Lists
        $this->info('Generating Todo Lists...');
        foreach ($teacherIds as $tid) {
            // Delete existing to avoid duplicates if rerun
            TeacherTodoList::where('teacher_id', $tid)->delete();
            for ($i = 0; $i < rand(15, 30); $i++) {
                $tdl = new TeacherTodoList();
                $tdl->teacher_id = $tid;
                $tdl->tanggal = Carbon::now()->subDays(rand(0, 30));
                $tdl->tugas = 'Tugas Rutin Harian ' . rand(1, 100);
                $isDone = rand(0, 100) > 30 ? true : false;
                $tdl->is_done = $isDone;
                $tdl->completed_at = $isDone ? Carbon::now()->subDays(rand(0, 30)) : null;
                $tdl->save();
            }
        }

        // Generate Programs (Pillars)
        $this->info('Generating Programs (Pillars)...');
        
        // KS
        $ksId = User::role('kepala_sekolah')->first()->id ?? $teacherIds[0];
        $wkId = User::role('waka_kurikulum')->first()->id ?? $teacherIds[1];
        $wksId = User::role('waka_kesiswaan')->first()->id ?? $teacherIds[2];
        $hmId = User::role('humas')->first()->id ?? $teacherIds[6];
        $bkId = User::role('bk')->first()->id ?? $teacherIds[4];

        WorkProgram::truncate();
        $statuses = ['mendatang', 'berjalan', 'selesai'];
        for ($i = 1; $i <= 8; $i++) {
            $m = new WorkProgram();
            $m->nama_program = 'Supervisi Akademik & Mutu Tahap ' . $i;
            $m->divisi = 'kepala_sekolah';
            $m->status_pengajuan = 'disetujui_ks';
            $m->status_pelaksanaan = $statuses[array_rand($statuses)];
            $m->penanggung_jawab_id = $ksId;
            $m->tanggal_mulai = Carbon::now()->subDays(rand(0, 30));
            $m->tanggal_selesai = Carbon::now()->addDays(rand(10, 60));
            $m->save();
        }

        ProgramKurikulum::truncate();
        ProgramKesiswaan::truncate();
        $statusesK = ['Rencana', 'Sedang Berjalan', 'Selesai', 'Batal'];
        for ($i = 1; $i <= 10; $i++) {
            $pk = new ProgramKurikulum();
            $pk->user_id = $wkId;
            $pk->tahun_ajaran = '2026/2027';
            $pk->nama_program = 'Pengembangan Kurikulum Modul ' . $i;
            $pk->kategori = 'Pelaksanaan KBM';
            $pk->tipe_pelaksanaan = 'Semesteran';
            $pk->sifat = 'Wajib';
            $pk->status_pelaksanaan = $statusesK[array_rand($statusesK)];
            $pk->sasaran = 'Siswa Kelas VII-IX';
            $pk->tujuan = 'Meningkatkan Mutu Akademik';
            $pk->save();

            $pks = new ProgramKesiswaan();
            $pks->user_id = $wksId;
            $pks->tahun_ajaran = '2026/2027';
            $pks->nama_program = 'Kegiatan Pembinaan Karakter ' . $i;
            $pks->kategori = 'Ekstrakurikuler';
            $pks->tipe_pelaksanaan = 'Bulanan';
            $pks->sifat = 'Wajib';
            $pks->status_pelaksanaan = $statusesK[array_rand($statusesK)];
            $pks->sasaran = 'Seluruh Siswa';
            $pks->tujuan = 'Pembinaan Akhlak';
            $pks->save();
        }

        HumasProgram::truncate();
        $statusesH = ['belum', 'sudah'];
        for ($i = 1; $i <= 6; $i++) {
            $hp = new HumasProgram();
            $hp->nama = 'Rapat Kolaborasi Wali Murid ' . $i;
            $hp->tanggal = Carbon::now()->addDays(rand(-10, 10));
            $hp->status = $statusesH[array_rand($statusesH)];
            $hp->save();
        }

        BkWorkProgram::truncate();
        $statusesBk = ['Rencana', 'Sedang Berjalan', 'Terlaksana', 'Tidak Terlaksana'];
        for ($i = 1; $i <= 7; $i++) {
            $bk = new BkWorkProgram();
            $bk->user_id = $bkId;
            $bk->tahun_ajaran = '2026/2027';
            $bk->judul_program = 'Konseling Kelompok ' . $i;
            $bk->bidang_layanan = 'Pribadi';
            $bk->tipe_pelaksanaan = 'Harian';
            $bk->sifat = 'Rutin';
            $bk->status = $statusesBk[array_rand($statusesBk)];
            $bk->sasaran = 'Siswa Kelas VII-IX';
            $bk->tujuan = 'Bimbingan dan Konseling';
            $bk->waktu_pelaksanaan = 'Tiap Hari';
            $bk->save();
        }
        
        // Classrooms & Students
        $this->info('Generating Classrooms & Students...');
        $classes = ['VII-A', 'VII-B', 'VIII-A', 'VIII-B', 'IX-A', 'IX-B'];
        foreach ($classes as $c) {
            $cls = Classroom::where('name', $c)->first();
            if (!$cls) {
                $cls = new Classroom();
                $cls->name = $c;
                $cls->tingkat = explode('-', $c)[0];
                $cls->wali_kelas_id = $teacherIds[array_rand($teacherIds)];
                $cls->save();
            }
            
            for ($s = 1; $s <= 15; $s++) {
                $nis = '100' . $cls->id . sprintf('%03d', $s);
                $st = Student::where('nis', $nis)->first();
                if (!$st) {
                    $st = new Student();
                    $st->nis = $nis;
                    $st->name = 'Siswa ' . $c . ' ' . $s;
                    $st->classroom_id = $cls->id;
                    $st->tanggal_lahir = Carbon::now()->subYears(rand(12, 15));
                    $st->jenis_kelamin = rand(0, 1) ? 'L' : 'P';
                    $st->character_score = rand(70, 100);
                    $st->save();
                }
            }
        }

        $this->info('Dummy data generated successfully!');
    }
}
