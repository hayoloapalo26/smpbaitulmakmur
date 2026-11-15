<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\RekapitulasiSkorPelanggaranPage;
use App\Filament\Staff\Resources\StudentCaseResource;
use App\Models\Classroom;
use App\Models\DutySchedule;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentCase;
use App\Models\StudentViolationLog;
use App\Models\User;
use App\Services\StudentViolationReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentViolationAndCaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected Classroom $classroom;
    protected Student $student;
    protected User $waliKelas;
    protected User $wakaKesiswaan;
    protected User $guruPiket;
    protected User $kepalaSekolah;

    protected function setUp(): void
    {
        parent::setUp();

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));

        foreach (['wali_kelas', 'waka_kesiswaan', 'bk', 'guru_piket', 'kepala_sekolah', 'superadmin'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        Institution::firstOrCreate(['id' => 1], [
            'name' => 'Yayasan Baitul Makmur',
            'slug' => 'yayasan',
        ]);

        $this->institution = Institution::firstOrCreate(['id' => 2], [
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smpi',
        ]);

        $this->waliKelas = User::factory()->create([
            'name'           => 'Ust. Ahmad Wali Kelas',
            'institution_id' => $this->institution->id,
        ]);
        $this->waliKelas->assignRole('wali_kelas');

        $this->classroom = Classroom::create([
            'name'           => 'Kelas 8-B',
            'tingkat'        => '8',
            'wali_kelas_id'  => $this->waliKelas->id,
            'institution_id' => $this->institution->id,
            'tipe'           => Classroom::TIPE_REGULER,
        ]);

        $this->wakaKesiswaan = User::factory()->create([
            'name'           => 'Ust. Kesiswaan M.Pd',
            'institution_id' => $this->institution->id,
        ]);
        $this->wakaKesiswaan->assignRole('waka_kesiswaan');

        $this->guruPiket = User::factory()->create([
            'name'           => 'Ust. Piket Hari Ini',
            'institution_id' => $this->institution->id,
        ]);
        $this->guruPiket->assignRole('guru_piket');

        $this->kepalaSekolah = User::factory()->create([
            'name'           => 'Dr. Kepala Sekolah',
            'institution_id' => $this->institution->id,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        $this->student = Student::create([
            'name'            => 'Rian Firmansyah',
            'nis'             => '2026888',
            'nisn'            => '0099887766',
            'jenis_kelamin'   => 'L',
            'classroom_id'    => $this->classroom->id,
            'institution_id'  => $this->institution->id,
            'character_score' => 100,
        ]);
    }

    public function test_access_control_wali_kelas_always_has_access(): void
    {
        $this->actingAs($this->waliKelas);

        $this->assertTrue(StudentCaseResource::canAccess());
        $this->assertTrue(StudentCaseResource::canCreate());
        $this->assertEquals('Fitur Wali Kelas', StudentCaseResource::getNavigationGroup());

        $this->assertTrue(RekapitulasiSkorPelanggaranPage::canAccess());
        $this->assertEquals('Fitur Wali Kelas', RekapitulasiSkorPelanggaranPage::getNavigationGroup());
    }

    public function test_access_control_waka_kesiswaan_always_has_access(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        $this->assertTrue(StudentCaseResource::canAccess());
        $this->assertTrue(StudentCaseResource::canCreate());
        $this->assertEquals('Kesiswaan & Karakter', StudentCaseResource::getNavigationGroup());

        $this->assertTrue(RekapitulasiSkorPelanggaranPage::canAccess());
        $this->assertEquals('Kesiswaan & Karakter', RekapitulasiSkorPelanggaranPage::getNavigationGroup());
    }

    public function test_access_control_guru_piket_only_accessible_when_on_duty_today(): void
    {
        $this->actingAs($this->guruPiket);

        $todayIndoDay = \App\Models\Schedule::getIndonesianDayName(Carbon::today());
        $otherDay = ($todayIndoDay === 'Senin') ? 'Selasa' : 'Senin';

        // 1. Saat bertugas di hari lain (TIDAK bertugas hari ini):
        $schedule = DutySchedule::create([
            'teacher_id'     => $this->guruPiket->id,
            'hari'           => $otherDay,
            'institution_id' => $this->institution->id,
        ]);

        $this->assertFalse($this->guruPiket->isPiketToday());
        $this->assertFalse(StudentCaseResource::canAccess());
        $this->assertFalse(StudentCaseResource::canCreate());
        $this->assertFalse(RekapitulasiSkorPelanggaranPage::canAccess());

        // 2. Berikan jadwal piket hari ini ke guru piket
        $schedule->update([
            'hari' => $todayIndoDay,
        ]);

        $this->assertTrue($this->guruPiket->isPiketToday());
        $this->assertTrue(StudentCaseResource::canAccess());
        $this->assertTrue(StudentCaseResource::canCreate());
        $this->assertEquals('Tugas Guru Piket', StudentCaseResource::getNavigationGroup());

        $this->assertTrue(RekapitulasiSkorPelanggaranPage::canAccess());
        $this->assertEquals('Tugas Guru Piket', RekapitulasiSkorPelanggaranPage::getNavigationGroup());
    }

    public function test_creating_student_case_automatically_integrates_into_violation_logs_and_rekapitulasi(): void
    {
        $this->actingAs($this->waliKelas);

        $case = StudentCase::create([
            'institution_id'   => $this->institution->id,
            'student_id'       => $this->student->id,
            'wali_kelas_id'    => $this->waliKelas->id,
            'nomor_kasus'      => 'KSS-2026-0099',
            'tanggal_kejadian' => now()->toDateString(),
            'kategori_kasus'   => ['Berkelahi', 'Merokok'],
            'deskripsi'        => 'Terlibat perkelahian dan kedapatan merokok di belakang kantin',
            'poin_pelanggaran' => 15,
            'catatan_wali_kelas' => 'Pembinaan awal di ruang wali kelas',
            'status'           => 'proses',
        ]);

        // Pastikan student_violation_logs terisi otomatis
        $violationLog = StudentViolationLog::where('student_case_id', $case->id)->first();
        $this->assertNotNull($violationLog);
        $this->assertEquals($this->student->id, $violationLog->student_id);
        $this->assertEquals(15, $violationLog->poin);
        $this->assertStringContainsString('KSS-2026-0099', $violationLog->bentuk_pelanggaran);
        $this->assertStringContainsString('Berkelahi', $violationLog->bentuk_pelanggaran);

        // Pastikan muncul di data Rekapitulasi Harian
        $harianData = StudentViolationReportService::getHarianData(now()->toDateString(), $this->classroom->id);
        $this->assertTrue($harianData->contains('id', $violationLog->id));

        // Pastikan poin terakumulasi di Rekapitulasi Mingguan
        $year = (int) now()->format('Y');
        $month = (int) now()->format('m');
        $week = min(5, (int) ceil((int) now()->format('d') / 7));
        $mingguanData = StudentViolationReportService::getMingguanData($year, $month, $week, $this->classroom->id);
        $studentRow = $mingguanData->firstWhere('student.id', $this->student->id);
        $this->assertNotNull($studentRow);
        $this->assertEquals(15, $studentRow['total_poin']);

        // Pastikan skor karakter siswa berkurang secara sinkron
        $this->student->refresh();
        $this->assertEquals(85, $this->student->character_score);
    }

    public function test_updating_student_case_updates_violation_log(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        $case = StudentCase::create([
            'institution_id'   => $this->institution->id,
            'student_id'       => $this->student->id,
            'wali_kelas_id'    => $this->waliKelas->id,
            'nomor_kasus'      => 'KSS-2026-0100',
            'tanggal_kejadian' => now()->toDateString(),
            'kategori_kasus'   => ['Terlambat'],
            'deskripsi'        => 'Terlambat masuk sekolah',
            'poin_pelanggaran' => 5,
            'status'           => 'proses',
        ]);

        $this->assertDatabaseHas('student_violation_logs', [
            'student_case_id' => $case->id,
            'poin'            => 5,
        ]);

        // Update kasus menjadi lebih berat
        $case->update([
            'kategori_kasus'   => ['Bullying / Perundungan'],
            'deskripsi'        => 'Melakukan perundungan verbal terhadap adik kelas',
            'poin_pelanggaran' => 25,
        ]);

        $this->assertDatabaseHas('student_violation_logs', [
            'student_case_id' => $case->id,
            'poin'            => 25,
        ]);
    }

    public function test_deleting_student_case_removes_linked_violation_log(): void
    {
        $this->actingAs($this->waliKelas);

        $case = StudentCase::create([
            'institution_id'   => $this->institution->id,
            'student_id'       => $this->student->id,
            'wali_kelas_id'    => $this->waliKelas->id,
            'nomor_kasus'      => 'KSS-2026-0101',
            'tanggal_kejadian' => now()->toDateString(),
            'kategori_kasus'   => ['Lainnya'],
            'deskripsi'        => 'Uji coba penghapusan',
            'poin_pelanggaran' => 5,
            'status'           => 'proses',
        ]);

        $caseId = $case->id;
        $this->assertDatabaseHas('student_violation_logs', ['student_case_id' => $caseId]);

        $case->delete();
        $this->assertDatabaseMissing('student_violation_logs', ['student_case_id' => $caseId]);
    }
}
