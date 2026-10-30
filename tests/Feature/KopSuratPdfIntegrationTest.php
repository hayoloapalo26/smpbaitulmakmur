<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\OsisProgram;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherCompetencyTest;
use App\Models\TeacherSupervision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KopSuratPdfIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $yayasan;
    protected Institution $smp;
    protected User $admin;
    protected Classroom $classroom;
    protected Student $student;
    protected Subject $subject;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->yayasan = Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->smp = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        $this->academicYear = AcademicYear::create([
            'institution_id' => $this->smp->id,
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
        ]);

        $this->classroom = Classroom::create([
            'institution_id' => $this->smp->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'VII-A',
            'tingkat' => 7,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Administrator Sekolah',
            'email' => 'admin@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->smp->id,
        ]);
        $this->admin->assignRole('superadmin');

        $this->student = Student::create([
            'institution_id' => $this->smp->id,
            'classroom_id' => $this->classroom->id,
            'name' => 'Muhammad Bilal',
            'nis' => '2026001',
            'nisn' => '0012345678',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-10',
            'character_score' => 95,
            'is_administrasi_lunas' => true,
        ]);

        $this->subject = Subject::create([
            'institution_id' => $this->smp->id,
            'name' => 'Matematika',
            'kode' => 'MTK',
        ]);
    }

    /**
     * Test komponen dasar kop surat memuat logo base64, alamat, email, dan instagram.
     */
    public function test_kop_surat_blade_renders_logo_and_complete_contact_info(): void
    {
        $html = view('pdf.kop_surat', ['institutionName' => 'SMP ISLAM BAITUL MAKMUR MALANG'])->render();

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Gg. 17B Jl. Raya Sawojajar No.58, Sawojajar, Kec. Kedungkandang, Kota Malang, Jawa Timur 65139', $html);
        $this->assertStringContainsString('adminsmp@baitulmakmur.sch.id', $html);
        $this->assertStringContainsString('@smpibaitulmakmurmlg', $html);
    }

    /**
     * Test E-Rapor Akademik Siswa memuat kop surat resmi.
     */
    public function test_rapor_akademik_pdf_contains_official_kop_surat(): void
    {
        Grade::create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'semester' => 'Ganjil',
            'type' => 'uas',
            'score' => 88,
        ]);

        $response = $this->actingAs($this->admin)->get(route('portal.rapor', $this->student));
        $response->assertStatus(200);

        // Render view langsung untuk memeriksa string HTML sebelum dienkapsulasi binary PDF
        $grades = Grade::where('student_id', $this->student->id)->get();
        $html = view('pdf.rapor_akademik', ['student' => $this->student, 'grades' => $grades])->render();

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Gg. 17B Jl. Raya Sawojajar No.58', $html);
        $this->assertStringContainsString('adminsmp@baitulmakmur.sch.id', $html);
        $this->assertStringContainsString('@smpibaitulmakmurmlg', $html);
    }

    /**
     * Test Rapor Karakter Siswa memuat kop surat resmi.
     */
    public function test_character_report_pdf_contains_official_kop_surat(): void
    {
        $response = $this->actingAs($this->admin)->get(route('report.character', $this->student));
        $response->assertStatus(200);

        $html = view('pdf.character_report', ['student' => $this->student, 'logs' => collect()])->render();

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Gg. 17B Jl. Raya Sawojajar No.58', $html);
        $this->assertStringContainsString('adminsmp@baitulmakmur.sch.id', $html);
        $this->assertStringContainsString('@smpibaitulmakmurmlg', $html);
    }

    /**
     * Test Rekap Absensi & Sholat memuat kop surat resmi.
     */
    public function test_prayer_report_pdf_contains_official_kop_surat(): void
    {
        $response = $this->actingAs($this->admin)->get(route('report.prayer', ['institution_id' => $this->smp->id]));
        $response->assertStatus(200);

        $html = view('pdf.prayer_report', [
            'students' => Student::with(['dailyAttendances', 'prayerAttendances', 'classroom'])->get(),
            'institutionName' => $this->smp->name,
        ])->render();

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Gg. 17B Jl. Raya Sawojajar No.58', $html);
        $this->assertStringContainsString('adminsmp@baitulmakmur.sch.id', $html);
        $this->assertStringContainsString('@smpibaitulmakmurmlg', $html);
    }

    /**
     * Test Laporan Program OSIS memuat kop surat resmi.
     */
    public function test_osis_report_pdf_contains_official_kop_surat(): void
    {
        OsisProgram::create([
            'nama_program' => 'Peringatan Hari Santri Nasional',
            'tanggal' => '2026-10-22',
            'status' => 'selesai',
        ]);

        $response = $this->actingAs($this->admin)->get(route('report.osis'));
        $response->assertStatus(200);

        $html = view('pdf.osis_report', ['programs' => OsisProgram::all()])->render();

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Gg. 17B Jl. Raya Sawojajar No.58', $html);
        $this->assertStringContainsString('adminsmp@baitulmakmur.sch.id', $html);
        $this->assertStringContainsString('@smpibaitulmakmurmlg', $html);
    }

    /**
     * Test Supervisi Pembelajaran Guru memuat logo dan informasi alamat resmi.
     */
    public function test_supervisi_report_card_contains_official_kop_surat(): void
    {
        $supervision = TeacherSupervision::create([
            'teacher_id' => $this->admin->id,
            'supervisor_id' => $this->admin->id,
            'institution_id' => $this->smp->id,
            'tanggal' => '2026-10-15',
            'mata_pelajaran' => 'Matematika',
            'kelas_semester' => 'VII / Ganjil',
            'score' => 85,
        ]);

        $response = $this->actingAs($this->admin)->get(route('supervisi.report-card.pdf', $supervision));
        $response->assertStatus(200);

        $html = view('pdf.supervisi_report_card', ['supervision' => $supervision])->render();

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Gg. 17B Jl. Raya Sawojajar No.58', $html);
        $this->assertStringContainsString('adminsmp@baitulmakmur.sch.id', $html);
        $this->assertStringContainsString('@smpibaitulmakmurmlg', $html);
    }

    /**
     * Test Raport PKG memuat logo dan informasi alamat resmi.
     */
    public function test_ukg_report_card_contains_official_kop_surat(): void
    {
        $ukg = TeacherCompetencyTest::create([
            'teacher_id' => $this->admin->id,
            'evaluator_id' => $this->admin->id,
            'institution_id' => $this->smp->id,
            'academic_year_id' => $this->academicYear->id,
            'assessment_date' => '2026-10-15',
            'score' => 88.5,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)->get(route('ukg.report-card.pdf', $ukg));
        $response->assertStatus(200);

        $html = view('pdf.ukg_report_card', ['ukg' => $ukg])->render();

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Gg. 17B Jl. Raya Sawojajar No.58', $html);
        $this->assertStringContainsString('adminsmp@baitulmakmur.sch.id', $html);
        $this->assertStringContainsString('@smpibaitulmakmurmlg', $html);
    }
}
