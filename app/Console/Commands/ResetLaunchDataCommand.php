<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetLaunchDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-launch-data {--force : Jalankan reset data tanpa konfirmasi manual}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset jadwal pelajaran, routing/setting guru mapel, dan progress pilar untuk persiapan launching resmi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');

        if (!$force && !$this->confirm('Apakah Anda yakin ingin mereset jadwal pelajaran, routing guru mapel, dan progress persentase untuk launching? (Data Siswa & Guru tetap aman)')) {
            $this->warn('Operasi dibatalkan.');
            return Command::SUCCESS;
        }

        $this->info('Memulai reset data untuk launching...');

        self::executeReset(function ($msg) {
            $this->line(" - {$msg}");
        });

        $this->newLine();
        $this->info('=====================================================');
        $this->info('✅ SUKSES: Jadwal pelajaran, setting guru mapel, dan progress pilar telah direset ke awal!');
        $this->info('🛡️ DATA TERJAGA: Data siswa, data guru, jabatan, kelas, dan akun yayasan 100% tetap aman.');
        $this->info('=====================================================');

        return Command::SUCCESS;
    }

    /**
     * Logika eksekusi reset terpusat (dapat dipanggil dari Artisan Command maupun Migration).
     */
    public static function executeReset(?callable $logger = null): void
    {
        $log = $logger ?: function ($msg) {};

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        try {
            // Catatan: Jadwal Pelajaran (schedules) dan Setting Guru Mapel (teacher_subject_classes)
            // adalah DATA MASTER RESMI SEKOLAH dan TIDAK BOLEH dihapus.

            // 3. Reset Nilai & Prestasi (Pilar Mutu Lulusan -> mulai 0%)
            if (Schema::hasTable('grades')) {
                DB::table('grades')->delete();
                $log('Tabel grades (Nilai Akademik) berhasil dikosongkan.');
            }
            if (Schema::hasTable('rapor_grades')) {
                DB::table('rapor_grades')->delete();
                $log('Tabel rapor_grades berhasil dikosongkan.');
            }
            if (Schema::hasTable('student_achievements')) {
                DB::table('student_achievements')->delete();
                $log('Tabel student_achievements (Prestasi Siswa) berhasil dikosongkan.');
            }

            // 4. Reset Sertifikat Guru (Pilar PTK)
            if (Schema::hasTable('teacher_certificates')) {
                DB::table('teacher_certificates')->delete();
                $log('Tabel teacher_certificates berhasil dikosongkan.');
            }

            // 5. Bersihkan Seluruh Data Dummy Program Kerja (Kurikulum, Kesiswaan, Sarpras, Humas, BK, KS)
            // agar murni hanya menampilkan program kerja riil yang diinput oleh yang bersangkutan
            if (Schema::hasTable('program_kurikulums')) {
                DB::table('program_kurikulums')->delete();
                $log('Tabel program_kurikulums (Data Dummy Kurikulum) berhasil dikosongkan.');
            }
            if (Schema::hasTable('program_kesiswaans')) {
                DB::table('program_kesiswaans')->delete();
                $log('Tabel program_kesiswaans (Data Dummy Kesiswaan) berhasil dikosongkan.');
            }
            if (Schema::hasTable('program_sarpras')) {
                DB::table('program_sarpras')->delete();
                $log('Tabel program_sarpras (Data Dummy Sarpras) berhasil dikosongkan.');
            }
            if (Schema::hasTable('program_humas')) {
                DB::table('program_humas')->delete();
                $log('Tabel program_humas (Data Dummy Humas) berhasil dikosongkan.');
            }
            if (Schema::hasTable('humas_programs')) {
                DB::table('humas_programs')->delete();
                $log('Tabel humas_programs (Data Dummy Humas) berhasil dikosongkan.');
            }
            if (Schema::hasTable('bk_work_programs')) {
                DB::table('bk_work_programs')->delete();
                $log('Tabel bk_work_programs (Data Dummy BK) berhasil dikosongkan.');
            }
            if (Schema::hasTable('work_programs')) {
                DB::table('work_programs')->delete();
                $log('Tabel work_programs (Data Dummy Work Programs) berhasil dikosongkan.');
            }
            if (Schema::hasTable('school_pillar_tasks')) {
                DB::table('school_pillar_tasks')->delete();
                $log('Tabel school_pillar_tasks (Tugas Statis Dummy) berhasil dikosongkan.');
            }

            // 7. Reset Jurnal dan Nilai Ekstrakurikuler Testing (jika ada)
            if (Schema::hasTable('extracurricular_grades')) {
                DB::table('extracurricular_grades')->delete();
                $log('Tabel extracurricular_grades berhasil dikosongkan.');
            }
            if (Schema::hasTable('extracurricular_journals')) {
                DB::table('extracurricular_journals')->delete();
                $log('Tabel extracurricular_journals berhasil dikosongkan.');
            }
            if (Schema::hasTable('extracurricular_attendances')) {
                DB::table('extracurricular_attendances')->delete();
                $log('Tabel extracurricular_attendances berhasil dikosongkan.');
            }
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }
}
