<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\BukuRaporPage;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\RaporGrade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\RaporCalculationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RaporAsliDanOlahanTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $waliKelas;
    protected User $wakaKurikulum;
    protected Classroom $class7A;
    protected Student $student;
    protected AcademicYear $academicYear;
    protected Subject $mapelMatematika;
    protected Subject $mapelIpa;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        Institution::firstOrCreate(['id' => 1], ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']);
        $this->institution = Institution::firstOrCreate(['id' => 2], ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-bm']);

        $this->waliKelas = User::create([
            'name' => 'Ust. Wali Kelas 7A',
            'username' => 'wali_7a',
            'email' => 'wali7a@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->waliKelas->assignRole('wali_kelas');

        $this->wakaKurikulum = User::create([
            'name' => 'Ust. Kurikulum',
            'username' => 'waka_kurikulum',
            'email' => 'kurikulum@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->wakaKurikulum->assignRole('waka_kurikulum');

        $this->class7A = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => 7,
            'academic_year' => '2026/2027',
            'wali_kelas_id' => $this->waliKelas->id,
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'name' => 'Ananda Fajar Santoso',
            'nis' => '7101',
            'nisn' => '0098765432',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-12',
            'classroom_id' => $this->class7A->id,
            'institution_id' => $this->institution->id,
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
            'institution_id' => $this->institution->id,
        ]);

        $this->mapelMatematika = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK',
            'institution_id' => $this->institution->id,
        ]);

        $this->mapelIpa = Subject::create([
            'name' => 'Ilmu Pengetahuan Alam',
            'kode' => 'IPA',
            'institution_id' => $this->institution->id,
        ]);
    }

    public function test_raw_original_grades_are_preserved(): void
    {
        // 1. Guru mapel menginput nilai asli (Matematika di bawah KKM)
        Grade::create([
            'student_id'       => $this->student->id,
            'subject_id'       => $this->mapelMatematika->id,
            'classroom_id'     => $this->class7A->id,
            'academic_year_id' => $this->academicYear->id,
            'semester'         => 'Ganjil',
            'type'             => 'pengetahuan',
            'score'            => 60.0,
            'institution_id'   => $this->institution->id,
        ]);
        Grade::create([
            'student_id'       => $this->student->id,
            'subject_id'       => $this->mapelMatematika->id,
            'classroom_id'     => $this->class7A->id,
            'academic_year_id' => $this->academicYear->id,
            'semester'         => 'Ganjil',
            'type'             => 'ujian',
            'score'            => 60.0,
            'institution_id'   => $this->institution->id,
        ]);

        // Nilai Asli: (60 + 2*60)/3 = 60
        $raporAsli = RaporCalculationService::getRaporData($this->student, $this->academicYear->id, 'Ganjil', 'asli');
        $mtkAsli = collect($raporAsli['subjects'])->firstWhere('name', 'Matematika');

        $this->assertNotNull($mtkAsli);
        $this->assertEquals(60.0, $mtkAsli['pengetahuan']['na']);
        $this->assertEquals(60.0, $mtkAsli['nilai_asli']['pengetahuan']);
        $this->assertFalse($mtkAsli['is_edited']);
        $this->assertFalse($raporAsli['has_edited_grades']);
    }

    public function test_edited_bantuan_grade_overrides_report_while_preserving_original(): void
    {
        // Guru mapel menginput nilai asli (60)
        Grade::create([
            'student_id'       => $this->student->id,
            'subject_id'       => $this->mapelMatematika->id,
            'classroom_id'     => $this->class7A->id,
            'academic_year_id' => $this->academicYear->id,
            'semester'         => 'Ganjil',
            'type'             => 'pengetahuan',
            'score'            => 60.0,
            'institution_id'   => $this->institution->id,
        ]);
        Grade::create([
            'student_id'       => $this->student->id,
            'subject_id'       => $this->mapelMatematika->id,
            'classroom_id'     => $this->class7A->id,
            'academic_year_id' => $this->academicYear->id,
            'semester'         => 'Ganjil',
            'type'             => 'ujian',
            'score'            => 60.0,
            'institution_id'   => $this->institution->id,
        ]);

        // Wali Kelas / Kurikulum memberikan Nilai Bantuan (Olahan) = 75 untuk rapor resmi
        RaporCalculationService::saveRaporGradeAdjustment(
            $this->student->id,
            $this->mapelMatematika->id,
            $this->academicYear->id,
            'Ganjil',
            75.0, // Nilai Pengetahuan Olah
            78.0, // Nilai Keterampilan Olah
            'Nilai Bantuan KKM (75) setelah remedial mandiri',
            null,
            null,
            $this->waliKelas->id
        );

        $this->assertDatabaseHas('rapor_grades', [
            'student_id'             => $this->student->id,
            'subject_id'             => $this->mapelMatematika->id,
            'nilai_pengetahuan_asli' => 60.0,
            'nilai_pengetahuan_olah' => 75.0,
            'nilai_keterampilan_olah'=> 78.0,
            'is_custom'              => 1,
        ]);

        // Cek Rapor Mode Olahan (Default Rapor Resmi): nilai aktif adalah 75
        $raporOlah = RaporCalculationService::getRaporData($this->student, $this->academicYear->id, 'Ganjil', 'olah');
        $mtkOlah = collect($raporOlah['subjects'])->firstWhere('name', 'Matematika');

        $this->assertTrue($raporOlah['has_edited_grades']);
        $this->assertTrue($mtkOlah['is_edited']);
        $this->assertEquals(75.0, $mtkOlah['pengetahuan']['na']);
        $this->assertEquals('B-', $mtkOlah['pengetahuan']['predikat']);
        $this->assertEquals(78.0, $mtkOlah['keterampilan']['na']);

        // Pastikan Nilai Asli tetap tersimpan utuh 60
        $this->assertEquals(60.0, $mtkOlah['nilai_asli']['pengetahuan']);

        // Cek Rapor Mode Asli Murni: nilai aktif tetap 60
        $raporAsli = RaporCalculationService::getRaporData($this->student, $this->academicYear->id, 'Ganjil', 'asli');
        $mtkAsli = collect($raporAsli['subjects'])->firstWhere('name', 'Matematika');
        $this->assertEquals(60.0, $mtkAsli['pengetahuan']['na']);
    }

    public function test_auto_boost_kkm_and_reset_classroom(): void
    {
        // Setup nilai asli 2 mapel di bawah KKM
        Grade::create([
            'student_id'       => $this->student->id,
            'subject_id'       => $this->mapelMatematika->id,
            'classroom_id'     => $this->class7A->id,
            'academic_year_id' => $this->academicYear->id,
            'semester'         => 'Ganjil',
            'type'             => 'pengetahuan',
            'score'            => 58.0,
            'institution_id'   => $this->institution->id,
        ]);
        Grade::create([
            'student_id'       => $this->student->id,
            'subject_id'       => $this->mapelIpa->id,
            'classroom_id'     => $this->class7A->id,
            'academic_year_id' => $this->academicYear->id,
            'semester'         => 'Ganjil',
            'type'             => 'pengetahuan',
            'score'            => 65.0,
            'institution_id'   => $this->institution->id,
        ]);

        // 1. Jalankan Auto Katrol Kelas Massal
        $boostCount = RaporCalculationService::autoBoostClassroomToKkm(
            $this->class7A->id,
            $this->academicYear->id,
            'Ganjil',
            75.0,
            $this->wakaKurikulum->id
        );

        $this->assertGreaterThanOrEqual(2, $boostCount);

        // Nilai Rapor Olahan sekarang menjadi 75
        $rapor = RaporCalculationService::getRaporData($this->student, $this->academicYear->id, 'Ganjil', 'olah');
        $mtk = collect($rapor['subjects'])->firstWhere('name', 'Matematika');
        $ipa = collect($rapor['subjects'])->firstWhere('name', 'Ilmu Pengetahuan Alam');

        $this->assertEquals(75.0, $mtk['pengetahuan']['na']);
        $this->assertEquals(75.0, $ipa['pengetahuan']['na']);
        $this->assertEquals(58.0, $mtk['nilai_asli']['pengetahuan']);
        $this->assertEquals(65.0, $ipa['nilai_asli']['pengetahuan']);

        // 2. Jalankan Reset Kelas ke Nilai Asli
        RaporCalculationService::resetClassroomToAsli($this->class7A->id, $this->academicYear->id, 'Ganjil');

        $raporReset = RaporCalculationService::getRaporData($this->student, $this->academicYear->id, 'Ganjil', 'olah');
        $mtkReset = collect($raporReset['subjects'])->firstWhere('name', 'Matematika');
        $ipaReset = collect($raporReset['subjects'])->firstWhere('name', 'Ilmu Pengetahuan Alam');

        // Kembali ke nilai asli murni
        $this->assertEquals(58.0, $mtkReset['pengetahuan']['na']);
        $this->assertEquals(65.0, $ipaReset['pengetahuan']['na']);
        $this->assertFalse($raporReset['has_edited_grades']);
    }

    public function test_buku_rapor_page_livewire_interaction(): void
    {
        $this->actingAs($this->waliKelas);

        Livewire::test(BukuRaporPage::class)
            ->assertSet('selectedClassroomId', $this->class7A->id)
            ->assertSet('modeNilai', 'olah')
            ->call('openEditRaporModal', $this->student->id)
            ->assertSet('showEditRaporModal', true)
            ->assertSet('editingStudentId', $this->student->id)
            ->call('closeEditRaporModal')
            ->assertSet('showEditRaporModal', false);
    }
}
