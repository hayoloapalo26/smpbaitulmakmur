<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\InputNilaiMapel;
use App\Filament\Staff\Resources\AlurTujuanPembelajaranResource;
use App\Filament\Staff\Resources\CapaianPembelajaranResource;
use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\GradeResource\Pages\ManageGrades;
use App\Filament\Staff\Resources\TujuanPembelajaranResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicSubjectGradingSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected AcademicYear $academicYear;
    protected Classroom $class7A;
    protected User $kurikulum;
    protected User $guruMapel;
    protected User $guruQuran;
    protected Student $student1;
    protected Student $student2;

    protected Subject $mapelMtk;
    protected Subject $mapelIpa;
    protected Subject $mapelBind;
    protected Subject $mapelQuran;
    protected Subject $mapelTahfidz;
    protected Subject $mapelTahsin;
    protected Subject $mapelYanbua;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);

        Institution::firstOrCreate(['id' => 1], ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']);
        $this->institution = Institution::firstOrCreate(['id' => 2], ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->kurikulum = User::create([
            'name' => 'Waka Kurikulum',
            'username' => 'waka_kurikulum',
            'email' => 'kurikulum@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->kurikulum->assignRole('waka_kurikulum');

        $this->class7A = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
            'wali_kelas_id' => $this->kurikulum->id,
            'is_active' => true,
        ]);

        $this->guruMapel = User::create([
            'name' => 'Guru Matematika & IPA',
            'username' => 'guru_mapel',
            'email' => 'mapel@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruMapel->assignRole('guru_mapel');

        $this->guruQuran = User::create([
            'name' => 'Guru Al-Qur\'an',
            'username' => 'guru_quran',
            'email' => 'quran@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruQuran->assignRole('guru_alquran');

        // Create Academic Subjects
        $this->mapelMtk = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK',
            'institution_id' => $this->institution->id,
        ]);
        $this->mapelIpa = Subject::create([
            'name' => 'Ilmu Pengetahuan Alam',
            'kode' => 'IPA',
            'institution_id' => $this->institution->id,
        ]);
        $this->mapelBind = Subject::create([
            'name' => 'Bahasa Indonesia',
            'kode' => 'BIND',
            'institution_id' => $this->institution->id,
        ]);

        // Create Quran-related Subjects (which should never appear in academic grading)
        $this->mapelQuran = Subject::create([
            'name' => 'Al-Qur’an',
            'kode' => 'QRN',
            'institution_id' => $this->institution->id,
        ]);
        $this->mapelTahfidz = Subject::create([
            'name' => 'Tahfidz Al-Qur\'an Juz 30',
            'kode' => 'TFZ',
            'institution_id' => $this->institution->id,
        ]);
        $this->mapelTahsin = Subject::create([
            'name' => 'Tahsin Tilawah',
            'kode' => 'THS',
            'institution_id' => $this->institution->id,
        ]);
        $this->mapelYanbua = Subject::create([
            'name' => 'Metode Yanbu\'a',
            'kode' => 'YNB',
            'institution_id' => $this->institution->id,
        ]);

        // Assign Guru Mapel to MTK and IPA
        TeacherSubjectClass::create([
            'teacher_id' => $this->guruMapel->id,
            'subject_id' => $this->mapelMtk->id,
            'classroom_id' => $this->class7A->id,
        ]);
        TeacherSubjectClass::create([
            'teacher_id' => $this->guruMapel->id,
            'subject_id' => $this->mapelIpa->id,
            'classroom_id' => $this->class7A->id,
        ]);

        // Create Students
        $this->student1 = Student::create([
            'name' => 'Santri Satu',
            'nis' => '1001',
            'classroom_id' => $this->class7A->id,
            'institution_id' => $this->institution->id,
            'tanggal_lahir' => '2012-01-01',
            'tempat_lahir' => 'Malang',
            'jenis_kelamin' => 'L',
        ]);
        $this->student2 = Student::create([
            'name' => 'Santri Dua',
            'nis' => '1002',
            'classroom_id' => $this->class7A->id,
            'institution_id' => $this->institution->id,
            'tanggal_lahir' => '2012-02-02',
            'tempat_lahir' => 'Malang',
            'jenis_kelamin' => 'P',
        ]);
    }

    /**
     * Test 1: Subject::akademik() scope excludes all Quran/Tahfidz/Tahsin/Yanbu'a subjects
     */
    public function test_subject_akademik_scope_excludes_all_quran_subjects(): void
    {
        $akademikSubjects = Subject::akademik()->pluck('name')->toArray();

        // Must include academic subjects
        $this->assertContains('Matematika', $akademikSubjects);
        $this->assertContains('Ilmu Pengetahuan Alam', $akademikSubjects);
        $this->assertContains('Bahasa Indonesia', $akademikSubjects);

        // Must NEVER include Quran subjects
        $this->assertNotContains('Al-Qur’an', $akademikSubjects);
        $this->assertNotContains('Tahfidz Al-Qur\'an Juz 30', $akademikSubjects);
        $this->assertNotContains('Tahsin Tilawah', $akademikSubjects);
        $this->assertNotContains('Metode Yanbu\'a', $akademikSubjects);

        // Model helper check
        $this->assertTrue($this->mapelQuran->isQuranSubject());
        $this->assertTrue($this->mapelTahfidz->isQuranSubject());
        $this->assertTrue($this->mapelTahsin->isQuranSubject());
        $this->assertTrue($this->mapelYanbua->isQuranSubject());
        $this->assertFalse($this->mapelMtk->isQuranSubject());
        $this->assertFalse($this->mapelIpa->isQuranSubject());
        $this->assertFalse($this->mapelBind->isQuranSubject());
    }

    /**
     * Test 2: InputNilaiMapel page only lists academic subjects and has no Quran filters
     */
    public function test_input_nilai_mapel_only_lists_academic_subjects(): void
    {
        $this->actingAs($this->kurikulum);

        $component = Livewire::test(InputNilaiMapel::class);

        $accessibleSubjects = $component->instance()->getAccessibleSubjects();
        $accessibleNames = $accessibleSubjects->pluck('name')->toArray();

        $this->assertContains('Matematika', $accessibleNames);
        $this->assertContains('Ilmu Pengetahuan Alam', $accessibleNames);
        $this->assertContains('Bahasa Indonesia', $accessibleNames);

        $this->assertNotContains('Al-Qur’an', $accessibleNames);
        $this->assertNotContains('Tahfidz Al-Qur\'an Juz 30', $accessibleNames);
        $this->assertNotContains('Tahsin Tilawah', $accessibleNames);
        $this->assertNotContains('Metode Yanbu\'a', $accessibleNames);

        // Check guru mapel view
        $this->actingAs($this->guruMapel);
        $componentGuru = Livewire::test(InputNilaiMapel::class);
        $guruSubjects = $componentGuru->instance()->getAccessibleSubjects();
        $guruSubjectNames = $guruSubjects->pluck('name')->toArray();

        $this->assertContains('Matematika', $guruSubjectNames);
        $this->assertContains('Ilmu Pengetahuan Alam', $guruSubjectNames);
        $this->assertNotContains('Al-Qur’an', $guruSubjectNames);
    }

    /**
     * Test 3: GradeResource form and Eloquent query only handle academic subjects
     */
    public function test_grade_resource_form_and_query_are_purely_academic(): void
    {
        $this->actingAs($this->kurikulum);

        // Create sample grades: one academic and one old quran grade
        $gradeAcademic = Grade::create([
            'student_id' => $this->student1->id,
            'subject_id' => $this->mapelMtk->id,
            'classroom_id' => $this->class7A->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'score' => 88,
            'type' => 'formatif',
            'teacher_id' => $this->guruMapel->id,
        ]);

        $gradeQuran = Grade::create([
            'student_id' => $this->student1->id,
            'subject_id' => $this->mapelQuran->id,
            'classroom_id' => $this->class7A->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'score' => 95,
            'type' => 'formatif',
            'teacher_id' => $this->guruQuran->id,
        ]);

        // Eloquent query must include academic and exclude quran
        $visibleGradeIds = GradeResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($gradeAcademic->id, $visibleGradeIds);
        $this->assertNotContains($gradeQuran->id, $visibleGradeIds);

        // Inspect Form schema components
        $form = GradeResource::form(new Form(Livewire::test(ManageGrades::class)->instance()));
        $flatFields = $form->getFlatFields();

        // filter_kelas_quran must NOT exist
        $this->assertArrayNotHasKey('filter_kelas_quran', $flatFields);

        // classroom_id and student_id must exist
        $this->assertArrayHasKey('classroom_id', $flatFields);
        $this->assertArrayHasKey('student_id', $flatFields);
        $this->assertArrayHasKey('subject_id', $flatFields);

        // Check options of subject_id
        $subjectSelect = $flatFields['subject_id'];
        $subjectOptions = $subjectSelect->getOptions();

        $this->assertArrayHasKey($this->mapelMtk->id, $subjectOptions);
        $this->assertArrayHasKey($this->mapelIpa->id, $subjectOptions);
        $this->assertArrayHasKey($this->mapelBind->id, $subjectOptions);
        $this->assertArrayNotHasKey($this->mapelQuran->id, $subjectOptions);
        $this->assertArrayNotHasKey($this->mapelTahfidz->id, $subjectOptions);
    }

    /**
     * Test 4: ManageGrades action input_nilai_rombel has no Quran filters and strictly uses academic subjects
     */
    public function test_manage_grades_rombel_action_strictly_academic(): void
    {
        $this->actingAs($this->kurikulum);

        $livewire = Livewire::test(ManageGrades::class);
        $livewire->mountAction('input_nilai_rombel');

        $form = $livewire->instance()->getMountedActionForm();
        $this->assertNotNull($form);

        $flatFields = $form->getFlatFields();

        // Ensure filter_kelas_quran is NOT present in rombel action
        $this->assertArrayNotHasKey('filter_kelas_quran', $flatFields);

        // Ensure classroom_id is present and required
        $this->assertArrayHasKey('classroom_id', $flatFields);
        $this->assertTrue($flatFields['classroom_id']->isRequired());

        // Ensure subject_id options do NOT include any Quran subjects
        $subjectOptions = $flatFields['subject_id']->getOptions();
        $this->assertArrayHasKey($this->mapelMtk->id, $subjectOptions);
        $this->assertArrayNotHasKey($this->mapelQuran->id, $subjectOptions);
        $this->assertArrayNotHasKey($this->mapelTahfidz->id, $subjectOptions);
        $this->assertArrayNotHasKey($this->mapelTahsin->id, $subjectOptions);
        $this->assertArrayNotHasKey($this->mapelYanbua->id, $subjectOptions);
    }
}
