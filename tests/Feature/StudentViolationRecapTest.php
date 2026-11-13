<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\RekapitulasiSkorPelanggaranPage;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentViolationApproval;
use App\Models\StudentViolationLog;
use App\Models\StudentViolationRecord;
use App\Models\User;
use App\Services\StudentViolationReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentViolationRecapTest extends TestCase
{
    use RefreshDatabase;

    protected User $guruPiket;
    protected User $waliKelas;
    protected User $guruBk;
    protected User $wakaKesiswaan;
    protected User $kepalaSekolah;
    protected Classroom $classroom;
    protected Student $student;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));

        // Siapkan roles jika belum ada
        foreach (['guru_piket', 'wali_kelas', 'bk', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        Institution::firstOrCreate(['id' => 1], [
            'name' => 'Yayasan Baitul Makmur',
            'slug' => 'yayasan',
        ]);

        $this->institution = Institution::firstOrCreate(['id' => 2], [
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smp',
        ]);

        $this->waliKelas = User::factory()->create([
            'name'           => 'Ust. Ahmad Dahlan, S.Pd',
            'institution_id' => $this->institution->id,
        ]);
        $this->waliKelas->assignRole('wali_kelas');

        $this->classroom = Classroom::create([
            'name'           => 'Kelas 7-A',
            'tingkat'        => '7',
            'wali_kelas_id'  => $this->waliKelas->id,
            'institution_id' => $this->institution->id,
            'tipe'           => Classroom::TIPE_REGULER,
        ]);

        $this->guruPiket = User::factory()->create([
            'name'           => 'Ust. Fajar Piket, S.Pd',
            'institution_id' => $this->institution->id,
        ]);
        $this->guruPiket->assignRole('guru_piket');

        $this->guruBk = User::factory()->create([
            'name'           => 'Usth. Sarah BK, S.Psi',
            'institution_id' => $this->institution->id,
        ]);
        $this->guruBk->assignRole('bk');

        $this->wakaKesiswaan = User::factory()->create([
            'name'           => 'Ust. Ridwan Kesiswaan, M.Pd',
            'institution_id' => $this->institution->id,
        ]);
        $this->wakaKesiswaan->assignRole('waka_kesiswaan');

        $this->kepalaSekolah = User::factory()->create([
            'name'           => 'Dr. H. Mulyadi, M.Pd.I',
            'institution_id' => $this->institution->id,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        $this->student = Student::create([
            'name'            => 'Muhammad Farhan',
            'nis'             => '2026001',
            'nisn'            => '0012345678',
            'jenis_kelamin'   => 'L',
            'classroom_id'    => $this->classroom->id,
            'institution_id'  => $this->institution->id,
            'character_score' => 80,
        ]);
    }

    public function test_rubrik_predikat_nilai_sikap_matches_specification(): void
    {
        // A : 0 - 5 Poin
        $this->assertEquals('A', StudentViolationLog::getPredikatNilaiSikap(0)['predikat']);
        $this->assertEquals('A', StudentViolationLog::getPredikatNilaiSikap(5)['predikat']);

        // A- : 6 - 15 Poin
        $this->assertEquals('A-', StudentViolationLog::getPredikatNilaiSikap(6)['predikat']);
        $this->assertEquals('A-', StudentViolationLog::getPredikatNilaiSikap(15)['predikat']);

        // B+ : 16 - 30 Poin
        $this->assertEquals('B+', StudentViolationLog::getPredikatNilaiSikap(16)['predikat']);
        $this->assertEquals('B+', StudentViolationLog::getPredikatNilaiSikap(30)['predikat']);

        // B : 31 - 45 Poin
        $this->assertEquals('B', StudentViolationLog::getPredikatNilaiSikap(31)['predikat']);
        $this->assertEquals('B', StudentViolationLog::getPredikatNilaiSikap(45)['predikat']);

        // B- : 46 - 60 Poin
        $this->assertEquals('B-', StudentViolationLog::getPredikatNilaiSikap(46)['predikat']);
        $this->assertEquals('B-', StudentViolationLog::getPredikatNilaiSikap(60)['predikat']);

        // C+ : 61 - 85 Poin
        $this->assertEquals('C+', StudentViolationLog::getPredikatNilaiSikap(61)['predikat']);
        $this->assertEquals('C+', StudentViolationLog::getPredikatNilaiSikap(85)['predikat']);

        // C : 86 - 105 Poin
        $this->assertEquals('C', StudentViolationLog::getPredikatNilaiSikap(86)['predikat']);
        $this->assertEquals('C', StudentViolationLog::getPredikatNilaiSikap(105)['predikat']);

        // C- : 106 - 120 Poin
        $this->assertEquals('C-', StudentViolationLog::getPredikatNilaiSikap(106)['predikat']);
        $this->assertEquals('C-', StudentViolationLog::getPredikatNilaiSikap(120)['predikat']);

        // D : 121 - 140 Poin
        $this->assertEquals('D', StudentViolationLog::getPredikatNilaiSikap(121)['predikat']);
        $this->assertEquals('D', StudentViolationLog::getPredikatNilaiSikap(140)['predikat']);

        // E : 141 - 150 Poin (dan >150 Poin)
        $this->assertEquals('E', StudentViolationLog::getPredikatNilaiSikap(141)['predikat']);
        $this->assertEquals('E', StudentViolationLog::getPredikatNilaiSikap(150)['predikat']);
        $this->assertEquals('E', StudentViolationLog::getPredikatNilaiSikap(200)['predikat']);
    }

    public function test_create_daily_violation_log_with_all_variables(): void
    {
        $log = StudentViolationLog::create([
            'tanggal'                => '2026-11-13',
            'jam'                    => '07:30',
            'student_id'             => $this->student->id,
            'bentuk_pelanggaran'     => 'Datang terlambat masuk gerbang 20 menit',
            'poin'                   => 10,
            'teacher_id'             => $this->guruPiket->id,
            'tindak_lanjut_langsung' => 'Ditegur lisan & diberikan arahan kedisiplinan di ruang piket',
        ]);

        $this->assertDatabaseHas('student_violation_logs', [
            'id'                     => $log->id,
            'jam'                    => '07:30',
            'bentuk_pelanggaran'     => 'Datang terlambat masuk gerbang 20 menit',
            'poin'                   => 10,
            'tindak_lanjut_langsung' => 'Ditegur lisan & diberikan arahan kedisiplinan di ruang piket',
        ]);

        // Verifikasi auto-fill classroom_id dari siswa
        $this->assertEquals($this->classroom->id, $log->classroom_id);
    }

    public function test_report_service_aggregations(): void
    {
        // 1. Buat violation log
        StudentViolationLog::create([
            'tanggal'                => '2026-11-10',
            'jam'                    => '07:15',
            'student_id'             => $this->student->id,
            'bentuk_pelanggaran'     => 'Atribut seragam tidak lengkap (tanpa dasi)',
            'poin'                   => 5,
            'teacher_id'             => $this->guruPiket->id,
            'tindak_lanjut_langsung' => 'Diberikan pinjaman dasi cadangan piket',
        ]);

        // Catatan Perkembangan Wali Kelas untuk minggu ke-2
        StudentViolationRecord::create([
            'student_id'         => $this->student->id,
            'classroom_id'       => $this->classroom->id,
            'periode_type'       => 'mingguan',
            'periode_key'        => '2026-M11-W2',
            'catatan_wali_kelas' => 'Siswa berjanji melengkapi atribut seragam besok',
        ]);

        // Test Mingguan
        $mingguan = StudentViolationReportService::getMingguanData(2026, 11, 2, $this->classroom->id);
        $firstRow = $mingguan->first();
        $this->assertEquals(5, $firstRow['total_poin']);
        $this->assertStringContainsString('tanpa dasi', $firstRow['jenis_pelanggaran']);
        $this->assertStringContainsString('Siswa berjanji', $firstRow['catatan_wali_kelas']);
        $this->assertStringContainsString('pinjaman dasi', $firstRow['tindak_lanjut_piket']);

        // Test Bulanan
        $bulanan = StudentViolationReportService::getBulananData(2026, 11, $this->classroom->id);
        $bRow = $bulanan->first();
        $this->assertEquals(5, $bRow['poin_bulan_ini']);
        $this->assertEquals(5, $bRow['total_akumulasi']);
    }

    public function test_rekap_page_interaction_and_tab_switching(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        $component = Livewire::test(RekapitulasiSkorPelanggaranPage::class)
            ->assertSuccessful()
            ->set('activeTab', 'harian')
            ->assertSee('1. Jurnal Harian (Piket)')
            ->set('activeTab', 'mingguan')
            ->assertSee('2. Rekap Mingguan')
            ->set('activeTab', 'bulanan')
            ->assertSee('3. Rekap Bulanan')
            ->set('activeTab', 'triwulan')
            ->assertSee('4. Rekap Triwulan (3 Bulan)')
            ->set('activeTab', 'semester')
            ->assertSee('5. Rekap 1 Semester');

        $component->assertSuccessful();
    }

    public function test_save_daily_violation_via_page(): void
    {
        $this->actingAs($this->guruPiket);

        Livewire::test(RekapitulasiSkorPelanggaranPage::class)
            ->set('formStudentId', $this->student->id)
            ->set('formTanggal', '2026-11-13')
            ->set('formJam', '08:00')
            ->set('formBentukPelanggaran', 'Meninggalkan kelas tanpa izin guru mapel')
            ->set('formPoin', 15)
            ->set('formTeacherId', $this->guruPiket->id)
            ->set('formTindakLanjut', 'Dipanggil ke ruang piket dan dibina')
            ->call('saveViolation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_violation_logs', [
            'student_id'         => $this->student->id,
            'bentuk_pelanggaran' => 'Meninggalkan kelas tanpa izin guru mapel',
            'poin'               => 15,
        ]);
    }

    public function test_toggle_acc_by_authorized_roles(): void
    {
        // 1. Guru Piket ACC Harian
        $this->actingAs($this->guruPiket);
        Livewire::test(RekapitulasiSkorPelanggaranPage::class)
            ->set('activeTab', 'harian')
            ->set('selectedTanggal', '2026-11-13')
            ->call('toggleAcc', 'piket')
            ->assertHasNoErrors();

        $approvalHarian = StudentViolationApproval::where('periode_type', 'harian')->where('periode_key', '2026-11-13')->first();
        $this->assertNotNull($approvalHarian);
        $this->assertTrue($approvalHarian->acc_piket);

        // 2. Wali Kelas ACC Mingguan
        $this->actingAs($this->waliKelas);
        Livewire::test(RekapitulasiSkorPelanggaranPage::class)
            ->set('activeTab', 'mingguan')
            ->set('selectedClassroomId', $this->classroom->id)
            ->set('selectedTahun', 2026)
            ->set('selectedBulan', 11)
            ->set('selectedMinggu', 2)
            ->call('toggleAcc', 'wali_kelas')
            ->assertHasNoErrors();

        $approvalMingguan = StudentViolationApproval::where('classroom_id', $this->classroom->id)
            ->where('periode_type', 'mingguan')
            ->first();
        $this->assertNotNull($approvalMingguan);
        $this->assertTrue($approvalMingguan->acc_wali_kelas);
    }

    public function test_pdf_print_route_returns_pdf_for_all_tabs(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        // Harian PDF
        $responseHarian = $this->get(route('academic.violation.rekap.pdf', [
            'tab'          => 'harian',
            'classroom_id' => $this->classroom->id,
            'tanggal'      => '2026-11-13',
        ]));
        $responseHarian->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $responseHarian->headers->get('Content-Type'));

        // Mingguan PDF
        $responseMingguan = $this->get(route('academic.violation.rekap.pdf', [
            'tab'          => 'mingguan',
            'classroom_id' => $this->classroom->id,
            'tahun'        => 2026,
            'bulan'        => 11,
            'minggu'       => 2,
        ]));
        $responseMingguan->assertStatus(200);

        // Bulanan PDF
        $responseBulanan = $this->get(route('academic.violation.rekap.pdf', [
            'tab'          => 'bulanan',
            'classroom_id' => $this->classroom->id,
            'tahun'        => 2026,
            'bulan'        => 11,
        ]));
        $responseBulanan->assertStatus(200);

        // Triwulan PDF
        $responseTriwulan = $this->get(route('academic.violation.rekap.pdf', [
            'tab'          => 'triwulan',
            'classroom_id' => $this->classroom->id,
            'tahun'        => 2026,
            'triwulan'     => 1,
        ]));
        $responseTriwulan->assertStatus(200);

        // Semester PDF
        $responseSemester = $this->get(route('academic.violation.rekap.pdf', [
            'tab'          => 'semester',
            'classroom_id' => $this->classroom->id,
            'semester'     => 'ganjil',
            'tahun_ajaran' => '2026/2027',
        ]));
        $responseSemester->assertStatus(200);
    }
}
