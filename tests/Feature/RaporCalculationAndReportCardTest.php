<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\RaporCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RaporCalculationAndReportCardTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $yayasan;
    protected Institution $institution;
    protected Classroom $classroom;
    protected AcademicYear $academicYear;
    protected User $waliKelas;
    protected Student $student1;
    protected Student $student2;
    protected Subject $subject1;
    protected Subject $subject2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->yayasan = Institution::create(['id' => 1, 'name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']);
        $this->institution = Institution::create(['id' => 2, 'name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp']);

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'orang_tua', 'guard_name' => 'web']);

        $this->waliKelas = User::factory()->create([
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->waliKelas->assignRole('wali_kelas');

        $this->classroom = Classroom::create([
            'name' => '7A',
            'institution_id' => $this->institution->id,
            'wali_kelas_id' => $this->waliKelas->id,
            'tingkat' => '7',
            'tipe' => 'reguler',
        ]);

        $this->academicYear = AcademicYear::create([
            'year' => '2026/2027',
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->subject1 = Subject::create([
            'name' => 'Pendidikan Agama Islam',
            'kode' => 'PAI',
            'institution_id' => $this->institution->id,
        ]);

        $this->subject2 = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK',
            'institution_id' => $this->institution->id,
        ]);

        $this->student1 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'nis' => '1001',
            'name' => 'Gerrard Hasan Suryawan',
            'tanggal_lahir' => '2012-05-10',
            'jenis_kelamin' => 'L',
            'alamat_siswa' => 'Jl. Sawojajar 17 B',
            'is_administrasi_lunas' => true,
            'character_score' => 85,
        ]);

        $this->student2 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'nis' => '1002',
            'name' => 'Arini',
            'tanggal_lahir' => '2012-08-15',
            'jenis_kelamin' => 'P',
            'alamat_siswa' => 'Jl. Danau Ranau',
            'is_administrasi_lunas' => true,
            'character_score' => 85,
        ]);
    }

    public function test_exact_formula_pengetahuan_matches_excel(): void
    {
        // Formula: NA = (RT-NH + 2 * UTS) / 3
        // Data sample 1: RT-NH 82, UTS 75 -> 77.33
        $na1 = RaporCalculationService::calculateNaPengetahuan(82.0, 75.0);
        $this->assertEquals(77.33, $na1);

        // Data sample 2: RT-NH 88, UTS 86 -> 86.67
        $na2 = RaporCalculationService::calculateNaPengetahuan(88.0, 86.0);
        $this->assertEquals(86.67, $na2);

        // Data sample 3: RT-NH 100, UTS 94 -> 96.00
        $na3 = RaporCalculationService::calculateNaPengetahuan(100.0, 94.0);
        $this->assertEquals(96.0, $na3);

        // Data sample 4: RT-NH 93, UTS 80 -> 84.33
        $na4 = RaporCalculationService::calculateNaPengetahuan(93.0, 80.0);
        $this->assertEquals(84.33, $na4);
    }

    public function test_exact_formula_konversi_and_predikat_matches_excel(): void
    {
        // Formula: Konversi = (NA / 15) - 2.66
        $k1 = RaporCalculationService::calculateKonversi(77.33);
        $this->assertEquals(2.50, $k1);
        $this->assertEquals('B-', RaporCalculationService::determinePredikat($k1, 77.33));

        $k2 = RaporCalculationService::calculateKonversi(86.67);
        $this->assertEquals(3.12, $k2);
        $this->assertEquals('B+', RaporCalculationService::determinePredikat($k2, 86.67));

        $k3 = RaporCalculationService::calculateKonversi(96.0);
        $this->assertEquals(3.74, $k3);
        $this->assertEquals('A', RaporCalculationService::determinePredikat($k3, 96.0));

        $k4 = RaporCalculationService::calculateKonversi(90.0);
        $this->assertEquals(3.34, $k4);
        $this->assertEquals('A-', RaporCalculationService::determinePredikat($k4, 90.0));

        $k5 = RaporCalculationService::calculateKonversi(0.0);
        $this->assertEquals(0.0, $k5);
        $this->assertEquals('D', RaporCalculationService::determinePredikat($k5, 0.0));
    }

    public function test_get_rapor_data_calculates_correct_totals_and_ranks(): void
    {
        // Student 1 grades:
        // PAI: RT-NH 82, UTS 75 -> NA 77.33
        Grade::create([
            'student_id' => $this->student1->id,
            'subject_id' => $this->subject1->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'pengetahuan',
            'score' => 82.0,
        ]);
        Grade::create([
            'student_id' => $this->student1->id,
            'subject_id' => $this->subject1->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'ujian',
            'score' => 75.0,
        ]);
        // PAI Keterampilan: 85
        Grade::create([
            'student_id' => $this->student1->id,
            'subject_id' => $this->subject1->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'keterampilan',
            'score' => 85.0,
        ]);

        $rapor = RaporCalculationService::getRaporData($this->student1, $this->academicYear->id, 'Ganjil');

        $this->assertEquals($this->student1->id, $rapor['student']->id);
        $this->assertNotEmpty($rapor['subjects']);

        // Check PAI row
        $paiRow = collect($rapor['subjects'])->firstWhere('subject.id', $this->subject1->id);
        $this->assertNotNull($paiRow);
        $this->assertEquals(77.33, $paiRow['pengetahuan']['na']);
        $this->assertEquals('B-', $paiRow['pengetahuan']['predikat']);
        $this->assertEquals(85.0, $paiRow['keterampilan']['na']);
        $this->assertEquals('B', $paiRow['keterampilan']['predikat']);
    }

    public function test_class_leger_generates_matrix_with_ranks(): void
    {
        // Student 1 total score higher
        Grade::create([
            'student_id' => $this->student1->id,
            'subject_id' => $this->subject1->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'pengetahuan',
            'score' => 95.0,
        ]);
        Grade::create([
            'student_id' => $this->student1->id,
            'subject_id' => $this->subject1->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'ujian',
            'score' => 90.0,
        ]);

        // Student 2 total score lower
        Grade::create([
            'student_id' => $this->student2->id,
            'subject_id' => $this->subject1->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'pengetahuan',
            'score' => 70.0,
        ]);
        Grade::create([
            'student_id' => $this->student2->id,
            'subject_id' => $this->subject1->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'ujian',
            'score' => 70.0,
        ]);

        $leger = RaporCalculationService::getClassLeger($this->classroom->id, $this->academicYear->id, 'Ganjil');

        $this->assertCount(2, $leger['rows']);
        $firstRank = $leger['rows'][0];
        $this->assertEquals($this->student1->id, $firstRank['student']->id);
        $this->assertEquals(1, $firstRank['class_rank']);
    }

    public function test_report_routes_are_accessible_for_authorized_users(): void
    {
        $this->actingAs($this->waliKelas);

        // 1. PDF Rapor Siswa
        $resRapor = $this->get(route('report.rapor.student.pdf', ['student' => $this->student1->id]));
        $resRapor->assertStatus(200);

        // 2. PDF Leger Kelas
        $resLeger = $this->get(route('report.rapor.leger.pdf', ['classroom' => $this->classroom->id]));
        $resLeger->assertStatus(200);

        // 3. Portal Rapor Siswa
        $resPortal = $this->get(route('portal.rapor', ['student' => $this->student1->id]));
        $resPortal->assertStatus(200);
    }
}
