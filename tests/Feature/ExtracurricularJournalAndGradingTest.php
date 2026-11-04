<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\ExtracurricularGradeResource;
use App\Filament\Staff\Resources\ExtracurricularJournalResource;
use App\Filament\Staff\Resources\ExtracurricularResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\ExtracurricularGrade;
use App\Models\ExtracurricularJournal;
use App\Models\ExtracurricularMember;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Services\RaporCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExtracurricularJournalAndGradingTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $admin;
    protected User $guruEskul;
    protected User $wakaKesiswaan;
    protected Extracurricular $eskulFutsal;
    protected Classroom $class7A;
    protected Student $student;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_ekstrakurikuler', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));

        \App\Models\Institution::firstOrCreate(['id' => 1], ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']);
        $this->institution = \App\Models\Institution::firstOrCreate(['id' => 2], ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-bm']);

        $this->admin = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $this->admin->assignRole('superadmin');

        $this->guruEskul = User::factory()->create([
            'name' => 'Coach Budi Futsal',
            'institution_id' => $this->institution->id,
        ]);
        $this->guruEskul->assignRole('guru_ekstrakurikuler');

        $this->wakaKesiswaan = User::factory()->create([
            'name' => 'Ust. Ahmad Kesiswaan',
            'institution_id' => $this->institution->id,
        ]);
        $this->wakaKesiswaan->assignRole('waka_kesiswaan');

        $this->class7A = Classroom::create([
            'name' => 'Kelas 7A',
            'tingkat' => 7,
            'academic_year' => '2026/2027',
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'name' => 'Muhammad Rizki Pratama',
            'nis' => '2026001',
            'nisn' => '0012345678',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-04-10',
            'classroom_id' => $this->class7A->id,
            'institution_id' => $this->institution->id,
            'postal_code' => '65139',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
            'institution_id' => $this->institution->id,
        ]);

        $this->eskulFutsal = Extracurricular::create([
            'name' => 'Futsal',
            'pembina_id' => $this->guruEskul->id,
            'hari' => 'Jumat',
            'jam_mulai' => '15:30',
            'jam_selesai' => '17:00',
            'lokasi' => 'Lapangan Olahraga SMP BM',
            'is_active' => true,
            'institution_id' => $this->institution->id,
        ]);
    }

    public function test_navigation_groups_and_role_access(): void
    {
        // 1. Guru Ekstrakurikuler
        $this->actingAs($this->guruEskul);
        $this->assertTrue(ExtracurricularJournalResource::canAccess());
        $this->assertTrue(ExtracurricularGradeResource::canAccess());
        $this->assertEquals('Tugas Guru Ekstrakurikuler', ExtracurricularJournalResource::getNavigationGroup());
        $this->assertEquals('Tugas Guru Ekstrakurikuler', ExtracurricularGradeResource::getNavigationGroup());

        // 2. Waka Kesiswaan
        $this->actingAs($this->wakaKesiswaan);
        $this->assertTrue(ExtracurricularJournalResource::canAccess());
        $this->assertTrue(ExtracurricularGradeResource::canAccess());
        $this->assertEquals('Kesiswaan & Karakter', ExtracurricularJournalResource::getNavigationGroup());
        $this->assertEquals('Kesiswaan & Karakter', ExtracurricularGradeResource::getNavigationGroup());
    }

    public function test_student_routing_into_extracurricular(): void
    {
        // Routing siswa ke eskul Futsal
        $member = ExtracurricularMember::create([
            'extracurricular_id' => $this->eskulFutsal->id,
            'student_id'         => $this->student->id,
            'is_active'          => true,
            'institution_id'     => $this->institution->id,
        ]);

        $this->assertDatabaseHas('extracurricular_members', [
            'extracurricular_id' => $this->eskulFutsal->id,
            'student_id'         => $this->student->id,
            'is_active'          => 1,
        ]);

        $this->assertTrue($this->eskulFutsal->members->contains($member));
        $this->assertTrue($this->eskulFutsal->students->contains($this->student));
    }

    public function test_guru_ekstrakurikuler_creates_journal_with_material_and_attendance(): void
    {
        // Setup routing
        ExtracurricularMember::create([
            'extracurricular_id' => $this->eskulFutsal->id,
            'student_id'         => $this->student->id,
            'is_active'          => true,
            'institution_id'     => $this->institution->id,
        ]);

        $this->actingAs($this->guruEskul);

        // Coach creates journal
        $journal = ExtracurricularJournal::create([
            'extracurricular_id' => $this->eskulFutsal->id,
            'pembina_id'         => $this->guruEskul->id,
            'tanggal'            => now()->toDateString(),
            'jam_mulai'          => '15:30',
            'jam_selesai'        => '17:00',
            'lokasi'             => 'Lapangan Futsal',
            'materi_kegiatan'    => 'Teknik Menendang Bola (Passing & Shooting)',
            'deskripsi_kegiatan' => '1. Pemanasan 15 menit. 2. Latihan variasi passing dan shooting 45 menit. 3. Mini match.',
            'status_verifikasi'  => 'menunggu_verifikasi',
            'institution_id'     => $this->institution->id,
        ]);

        // Catat presensi siswa yang dirouting ke eskul ini
        $attendance = ExtracurricularAttendance::create([
            'extracurricular_id'         => $this->eskulFutsal->id,
            'extracurricular_journal_id' => $journal->id,
            'student_id'                 => $this->student->id,
            'tanggal'                    => $journal->tanggal,
            'status'                     => 'hadir',
            'catatan'                    => 'Semangat dan antusias mempraktikkan teknik dasar.',
            'pembina_id'                 => $this->guruEskul->id,
            'institution_id'             => $this->institution->id,
        ]);

        $this->assertDatabaseHas('extracurricular_journals', [
            'id'                => $journal->id,
            'materi_kegiatan'   => 'Teknik Menendang Bola (Passing & Shooting)',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);

        $this->assertDatabaseHas('extracurricular_attendances', [
            'id'                         => $attendance->id,
            'extracurricular_journal_id' => $journal->id,
            'student_id'                 => $this->student->id,
            'status'                     => 'hadir',
        ]);

        $this->assertEquals(1, $journal->attendances()->count());
    }

    public function test_kesiswaan_views_and_verifies_journal_terima_laporan(): void
    {
        $journal = ExtracurricularJournal::create([
            'extracurricular_id' => $this->eskulFutsal->id,
            'pembina_id'         => $this->guruEskul->id,
            'tanggal'            => now()->toDateString(),
            'materi_kegiatan'    => 'Teknik Dribbling & Ball Control',
            'deskripsi_kegiatan' => 'Latihan kelincahan zig-zag cone dan kontrol bola',
            'status_verifikasi'  => 'menunggu_verifikasi',
            'institution_id'     => $this->institution->id,
        ]);

        $this->actingAs($this->wakaKesiswaan);

        // Kesiswaan menerima laporan dan memberi catatan evaluasi
        $journal->update([
            'status_verifikasi' => 'diterima',
            'catatan_kesiswaan' => 'Laporan sangat lengkap. Materi latihan sesuai dengan program semester.',
            'verified_at'       => now(),
            'verified_by'       => $this->wakaKesiswaan->id,
        ]);

        $this->assertDatabaseHas('extracurricular_journals', [
            'id'                => $journal->id,
            'status_verifikasi' => 'diterima',
            'catatan_kesiswaan' => 'Laporan sangat lengkap. Materi latihan sesuai dengan program semester.',
            'verified_by'       => $this->wakaKesiswaan->id,
        ]);

        $this->assertTrue($journal->fresh()->isVerified());
    }

    public function test_final_semester_grading_and_rapor_integration(): void
    {
        // Setup routing
        ExtracurricularMember::create([
            'extracurricular_id' => $this->eskulFutsal->id,
            'student_id'         => $this->student->id,
            'is_active'          => true,
            'institution_id'     => $this->institution->id,
        ]);

        $this->actingAs($this->guruEskul);

        // Guru eskul menginput nilai akhir semester
        $grade = ExtracurricularGrade::create([
            'extracurricular_id' => $this->eskulFutsal->id,
            'student_id'         => $this->student->id,
            'pembina_id'         => $this->guruEskul->id,
            'academic_year_id'   => $this->academicYear->id,
            'semester'           => 'Ganjil',
            'nilai_angka'        => 88.0,
            'predikat'           => 'A',
            'deskripsi'          => 'Sangat aktif dalam latihan futsal, memiliki kemampuan penguasaan bola dan kerjasama tim yang luar biasa.',
            'institution_id'     => $this->institution->id,
        ]);

        $this->assertDatabaseHas('extracurricular_grades', [
            'extracurricular_id' => $this->eskulFutsal->id,
            'student_id'         => $this->student->id,
            'nilai_angka'        => 88.0,
            'predikat'           => 'A',
        ]);

        // Verifikasi integrasi ke RaporCalculationService
        $raporData = RaporCalculationService::getRaporData($this->student, $this->academicYear->id, 'Ganjil');

        $this->assertNotEmpty($raporData['extracurriculars']);
        $eskulRapor = collect($raporData['extracurriculars'])->firstWhere('name', 'Futsal');

        $this->assertNotNull($eskulRapor);
        $this->assertEquals('A', $eskulRapor['nilai']);
        $this->assertEquals('Sangat aktif dalam latihan futsal, memiliki kemampuan penguasaan bola dan kerjasama tim yang luar biasa.', $eskulRapor['deskripsi']);
    }
}
