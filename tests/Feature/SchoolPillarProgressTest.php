<?php

namespace Tests\Feature;

use App\Models\BkWorkProgram;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\ProgramHumas;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use App\Models\SchoolPillar;
use App\Models\SchoolPillarTask;
use App\Models\User;
use App\Services\SchoolPillarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolPillarProgressTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $smp;
    protected User $kepalaSekolah;
    protected \App\Models\AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);

        Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->smp = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        $this->academicYear = \App\Models\AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->kepalaSekolah = User::create([
            'name' => 'Kepala Sekolah',
            'email' => 'kepsek_pilar@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        // Seed 9 pillars
        $pillarNames = [
            1 => 'Bidang Manajemen',
            2 => 'Mutu Lulusan',
            3 => 'Bidang Kurikulum dan Pembelajaran',
            4 => 'PTK (Pendidik dan Tenaga Kependidikan)',
            5 => 'Kesiswaan',
            6 => 'Sarpras (Sarana Prasarana)',
            7 => 'Keuangan',
            8 => 'Kehumasan',
            9 => 'Layanan Khusus Sekolah',
        ];

        foreach ($pillarNames as $order => $name) {
            SchoolPillar::create([
                'institution_id' => $this->smp->id,
                'name' => $name,
                'order' => $order,
            ]);
        }
    }

    /**
     * Test bahwa Pilar 1 memiliki 7 indikator turunan dari 7 pilar konstituen.
     */
    public function test_pilar_1_contains_seven_constituent_indicators(): void
    {
        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar1 = collect($pillars)->firstWhere('order', 1);

        $this->assertNotNull($pilar1);
        $this->assertEquals(1, $pilar1['order']);
        $this->assertEquals('Bidang Manajemen', $pilar1['name']);
        $this->assertEquals(7, $pilar1['total']); // 7 pilar konstituen
        $this->assertCount(7, $pilar1['tasks']);

        $taskCodes = collect($pilar1['tasks'])->pluck('code')->all();
        $this->assertContains('1.1', $taskCodes);
        $this->assertContains('1.2', $taskCodes);
        $this->assertContains('1.3', $taskCodes);
        $this->assertContains('1.4', $taskCodes);
        $this->assertContains('1.5', $taskCodes);
        $this->assertContains('1.6', $taskCodes);
        $this->assertContains('1.7', $taskCodes);
    }

    /**
     * Test jika ke-7 pilar mencapai indikator 100, maka Pilar Bidang Manajemen menjadi 100%.
     */
    public function test_pilar_1_reaches_100_percent_when_all_seven_constituents_reach_100(): void
    {
        // 1. Mutu Lulusan (Pilar 2) -> Input Nilai Akademik Siswa + Input Prestasi Siswa
        $student = \App\Models\Student::create([
            'institution_id' => $this->smp->id,
            'name' => 'Ahmad Fulan',
            'nis' => '12345',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-01',
        ]);
        $subject = \App\Models\Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK',
            'institution_id' => $this->smp->id,
        ]);
        \App\Models\Grade::create([
            'institution_id' => $this->smp->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->kepalaSekolah->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'score' => 90,
            'type' => 'formatif',
        ]);
        \App\Models\StudentAchievement::create([
            'institution_id' => $this->smp->id,
            'student_id' => $student->id,
            'nama_prestasi' => 'Juara 1 OSN Matematika',
            'kategori' => 'akademik',
            'tingkat' => 'Kabupaten',
            'tanggal' => now(),
            'input_by' => $this->kepalaSekolah->id,
        ]);

        // 2. Kurikulum (Pilar 3) -> Set ProgramKurikulum all 'Selesai'
        ProgramKurikulum::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Penyusunan KOSP & Modul',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // 3. PTK (Pilar 4) -> Input Profil Guru + Input Sertifikat Guru
        $this->kepalaSekolah->update([
            'nuptk' => '1234567890123456',
            'phone' => '081234567890',
            'nip' => '198501012010011001',
        ]);
        \App\Models\TeacherCertificate::create([
            'institution_id' => $this->smp->id,
            'teacher_id' => $this->kepalaSekolah->id,
            'nama_kegiatan' => 'Pelatihan Implementasi Kurikulum Merdeka',
            'kategori' => 'Pelatihan / Bimtek',
            'penyelenggara' => 'Kemdikbudristek',
            'nomor_sertifikat' => 'CERT-12345',
            'tanggal_pelaksanaan' => now(),
            'jumlah_jam' => 32,
            'drive_url' => 'https://drive.google.com/test',
        ]);

        // 4. Kesiswaan (Pilar 5) -> Set ProgramKesiswaan all 'Selesai'
        ProgramKesiswaan::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'LDKS & Ekstrakurikuler',
            'kategori' => 'Kesiswaan',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // 5. Sarpras (Pilar 6) -> Set ProgramSarpras all 'Selesai'
        ProgramSarpras::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Perawatan Lab & Kelas',
            'kategori' => 'Sarpras',
            'tipe_pelaksanaan' => 'Bulanan',
            'sifat' => 'Wajib',
            'tujuan' => 'Menjaga kelayakan sarana belajar',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // 6. Humas (Pilar 8) -> Set ProgramHumas all 'Selesai'
        ProgramHumas::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Pertemuan Wali Murid',
            'kategori' => 'Humas',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'tujuan' => 'Sosialisasi program sekolah dan evaluasi belajar',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // 7. BK (Pilar 9) -> Set BkWorkProgram all 'Terlaksana'
        BkWorkProgram::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'judul_program' => 'Konseling Peminatan Siswa',
            'bidang_layanan' => 'Karir',
            'sasaran' => 'Kelas 9',
            'waktu_pelaksanaan' => 'Semester 1',
            'tujuan' => 'Mengarahkan studi lanjut dan peminatan',
            'status' => 'Terlaksana',
        ]);

        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar1 = collect($pillars)->firstWhere('order', 1);

        // Verifikasi ke-7 pilar masing-masing bernilai 100%
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 2)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 3)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 4)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 5)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 6)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 8)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 9)['percentage']);

        // Verifikasi Pilar 1 (Bidang Manajemen) menjadi 100% dan 7/7 tugas tuntas
        $this->assertEquals(100, $pilar1['percentage']);
        $this->assertEquals(7, $pilar1['selesai']);
        $this->assertEquals(7, $pilar1['total']);

        // Semua indikator di Pilar 1 berstatus 'selesai'
        foreach ($pilar1['tasks'] as $task) {
            $this->assertEquals('selesai', $task->status);
        }
    }

    /**
     * Test ketercapaian Pilar 1 proporsional dan akurat saat sebagian pilar belum 100%.
     */
    public function test_pilar_1_calculates_proportional_average_accurately(): void
    {
        // 1. Kurikulum (Order 3): 1 selesai, 1 belum = 50%
        ProgramKurikulum::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Program 1',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);
        ProgramKurikulum::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Program 2',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Belum Selesai',
        ]);

        // 2. Kesiswaan (Order 5): 1 selesai = 100%
        ProgramKesiswaan::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Program OSIS',
            'kategori' => 'Kesiswaan',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // Pilar 2, 4, 6, 8, 9 masih 0%
        // Nilai pilar: 0, 50, 0, 100, 0, 0, 0
        // Rata-rata = (0 + 50 + 0 + 100 + 0 + 0 + 0) / 7 = 150 / 7 = 21.42... -> 21%

        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar1 = collect($pillars)->firstWhere('order', 1);

        $this->assertEquals(21, $pilar1['percentage']);
        $this->assertEquals(1, $pilar1['selesai']); // Hanya Pilar Kesiswaan yang sudah 100%
        $this->assertEquals(7, $pilar1['total']);
    }

    /**
     * Test halaman Dashboard Staff dapat memuat dan merender data 9 pilar tanpa error.
     */
    public function test_dashboard_staff_renders_updated_nine_pillars_cleanly(): void
    {
        $this->actingAs($this->kepalaSekolah);
        try {
            \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));
        } catch (\Throwable $e) {}

        \Livewire\Livewire::test(\App\Filament\Staff\Pages\Dashboard::class)
            ->assertSuccessful()
            ->assertSee('Bidang Manajemen')
            ->assertSee('Mutu Lulusan')
            ->assertSee('Bidang Kurikulum');
    }

    /**
     * Test Pilar 2 (Mutu Lulusan) dihitung dari fitur nilai akademik dan prestasi siswa.
     */
    public function test_pilar_2_mutu_lulusan_progress_from_akademik_and_prestasi(): void
    {
        $student = \App\Models\Student::create([
            'institution_id' => $this->smp->id,
            'name' => 'Siswa Satu',
            'nis' => '11111',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-01',
        ]);

        // Belum ada nilai dan belum ada prestasi -> 0%
        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar2 = collect($pillars)->firstWhere('order', 2);
        $this->assertEquals(0, $pilar2['percentage']);

        // Input Nilai Akademik Siswa -> 50%
        $subject = \App\Models\Subject::create([
            'name' => 'IPA',
            'kode' => 'IPA',
            'institution_id' => $this->smp->id,
        ]);
        \App\Models\Grade::create([
            'institution_id' => $this->smp->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->kepalaSekolah->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'score' => 85,
            'type' => 'formatif',
        ]);
        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar2 = collect($pillars)->firstWhere('order', 2);
        $this->assertEquals(50, $pilar2['percentage']);

        // Input Prestasi Siswa -> 100%
        \App\Models\StudentAchievement::create([
            'institution_id' => $this->smp->id,
            'student_id' => $student->id,
            'nama_prestasi' => 'Juara 1 Lomba MTQ',
            'kategori' => 'akademik',
            'tingkat' => 'Kota',
            'tanggal' => now(),
            'input_by' => $this->kepalaSekolah->id,
        ]);
        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar2 = collect($pillars)->firstWhere('order', 2);
        $this->assertEquals(100, $pilar2['percentage']);
    }

    /**
     * Test Pilar 4 (PTK) dihitung dari input profil guru dan input sertifikat guru.
     */
    public function test_pilar_4_ptk_progress_from_profil_and_sertifikat(): void
    {
        // Awal: Profil belum lengkap, sertifikat belum ada -> 0%
        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar4 = collect($pillars)->firstWhere('order', 4);
        $this->assertEquals(0, $pilar4['percentage']);

        // Lengkapi profil guru -> 50%
        $this->kepalaSekolah->update([
            'nuptk' => '9876543210123456',
            'phone' => '08987654321',
        ]);
        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar4 = collect($pillars)->firstWhere('order', 4);
        $this->assertEquals(50, $pilar4['percentage']);

        // Input sertifikat guru -> 100%
        \App\Models\TeacherCertificate::create([
            'institution_id' => $this->smp->id,
            'teacher_id' => $this->kepalaSekolah->id,
            'nama_kegiatan' => 'Workshop Media Pembelajaran Digital',
            'kategori' => 'Workshop / Lokakarya',
            'penyelenggara' => 'Dinas Pendidikan',
            'tanggal_pelaksanaan' => now(),
            'drive_url' => 'https://drive.google.com/cert-test',
        ]);
        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar4 = collect($pillars)->firstWhere('order', 4);
        $this->assertEquals(100, $pilar4['percentage']);
    }

    /**
     * Test Pilar Kurikulum, Kesiswaan, Sarpras, Humas, dan BK hanya bertambah dari program kerja yang Selesai.
     */
    public function test_work_programs_pillars_progress(): void
    {
        // Kurikulum: 1 Selesai dari 2 = 50%
        ProgramKurikulum::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Kurikulum 1',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);
        ProgramKurikulum::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Kurikulum 2',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Sedang Berjalan',
        ]);

        // Sarpras: 1 Selesai = 100%
        ProgramSarpras::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Renovasi Musholla',
            'kategori' => 'Sarpras',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Wajib',
            'tujuan' => 'Kenyamanan ibadah',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // Humas: 1 Selesai = 100%
        ProgramHumas::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Publikasi Kegiatan Sekolah',
            'kategori' => 'Humas',
            'tipe_pelaksanaan' => 'Bulanan',
            'sifat' => 'Wajib',
            'tujuan' => 'Meningkatkan citra positif',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // BK: 1 Belum Terlaksana = 0%
        BkWorkProgram::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'judul_program' => 'Tes Psikologi',
            'bidang_layanan' => 'Pribadi',
            'sasaran' => 'Kelas 7',
            'waktu_pelaksanaan' => 'Semester 1',
            'tujuan' => 'Pemetaan kepribadian siswa',
            'status' => 'Rencana',
        ]);

        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $this->assertEquals(50, collect($pillars)->firstWhere('order', 3)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 6)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 8)['percentage']);
        $this->assertEquals(0, collect($pillars)->firstWhere('order', 9)['percentage']);
    }

    /**
     * Test Pilar Keuangan tidak memiliki persentase dan menampilkan monitoring SPP.
     */
    public function test_pilar_keuangan_has_no_percentage_and_shows_spp_monitoring(): void
    {
        $student1 = \App\Models\Student::create([
            'institution_id' => $this->smp->id,
            'name' => 'Siswa Lunas',
            'nis' => '10001',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-01',
            'is_administrasi_lunas' => true,
        ]);
        $student2 = \App\Models\Student::create([
            'institution_id' => $this->smp->id,
            'name' => 'Siswa Nunggak',
            'nis' => '10002',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2010-02-02',
            'is_administrasi_lunas' => false,
        ]);

        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilarKeuangan = collect($pillars)->firstWhere('order', 7);

        $this->assertNotNull($pilarKeuangan);
        $this->assertFalse($pilarKeuangan['has_percentage']);
        $this->assertNull($pilarKeuangan['percentage']);
        $this->assertStringContainsString('SPP', $pilarKeuangan['name']);

        // Memuat tugas rekap SPP dan data per siswa
        $tasks = collect($pilarKeuangan['tasks']);
        $this->assertTrue($tasks->contains(fn($t) => str_contains($t->id, 'spp_summary')));
        $this->assertTrue($tasks->contains(fn($t) => str_contains($t->name, 'Siswa Lunas')));
        $this->assertTrue($tasks->contains(fn($t) => str_contains($t->name, 'Siswa Nunggak')));
    }

    /**
     * Test getOverallProgress() hanya merata-rata 8 pilar yang memiliki persentase.
     */
    public function test_get_overall_progress_strictly_averages_eight_pillars(): void
    {
        // Kurikulum (order 3) = 100%
        ProgramKurikulum::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Program Kurikulum 100',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // Pilar 1 (Bidang Manajemen) dihitung dari rata-rata 7 konstituen:
        // Konstituen: Pilar 2 (0), Pilar 3 (100), Pilar 4 (0), Pilar 5 (0), Pilar 6 (0), Pilar 8 (0), Pilar 9 (0)
        // Pilar 1 = 100 / 7 = 14%
        // Pilar dengan persentase:
        // Pilar 1: 14%
        // Pilar 2: 0%
        // Pilar 3: 100%
        // Pilar 4: 0%
        // Pilar 5: 0%
        // Pilar 6: 0%
        // Pilar 8: 0%
        // Pilar 9: 0%
        // Pilar 7 (Keuangan): null (tidak ikut dihitung)
        // Total dari 8 pilar = 14 + 0 + 100 + 0 + 0 + 0 + 0 + 0 = 114
        // Overall progress = 114 / 8 = 14.25 -> 14%

        $overall = SchoolPillarService::getOverallProgress($this->smp->id, $this->kepalaSekolah);
        $this->assertEquals(14, $overall);
    }

    /**
     * Test halaman Pilar Manajemen Dashboard memuat tampilan kartu dan accordion dengan benar.
     */
    public function test_pilar_manajemen_dashboard_page_renders_cleanly(): void
    {
        $this->actingAs($this->kepalaSekolah);
        try {
            \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));
        } catch (\Throwable $e) {}

        \Livewire\Livewire::test(\App\Filament\Staff\Pages\PilarManajemenDashboard::class)
            ->assertSuccessful()
            ->assertSee('Bidang Manajemen')
            ->assertSee('Data SPP');
    }
}
