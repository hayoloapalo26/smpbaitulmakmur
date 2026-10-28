<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\InputNilaiMapel;
use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\SubjectResource;
use App\Filament\Staff\Resources\TeacherSubjectClassResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmpSubjectsAndMultiSubjectGradingTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $kurikulum;
    protected User $teacher;
    protected Classroom $class7A;
    protected Classroom $class7B;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->kurikulum = User::create([
            'name' => 'Waka Kurikulum',
            'username' => 'kurikulum_test',
            'email' => 'kurikulum_test@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->kurikulum->assignRole('waka_kurikulum');

        $this->teacher = User::create([
            'name' => 'Ust. Ahmad Fauzi (Guru 2 Mapel)',
            'username' => 'guru_ahmad',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->teacher->assignRole('guru_mapel');

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));

        $this->academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            ['semester' => 'Ganjil', 'is_active' => true]
        );

        $this->class7A = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => '7A - Abu Bakar',
            'tingkat' => 7,
            'is_active' => true,
        ]);

        $this->class7B = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => '7B - Umar bin Khattab',
            'tingkat' => 7,
            'is_active' => true,
        ]);
    }

    public function test_all_17_smp_subjects_exist(): void
    {
        $expectedSubjects = [
            'SBDP',
            'BK',
            'Fiqih/Tarikh',
            'Informatika',
            'Bahasa Indonesia',
            'Matematika',
            'Nahwu/Shorof',
            'IPA',
            'PJOK',
            'Tauhid/Akhlak',
            'Hadits/Pegon',
            'IPS',
            'Bahasa Jawa',
            'Bahasa Inggris',
            'PAI',
            'PKN',
            'Al-Qur’an',
        ];

        // Ensure database migration has seeded/synced
        $this->artisan('migrate');

        foreach ($expectedSubjects as $subjectName) {
            $this->assertDatabaseHas('subjects', [
                'name' => $subjectName,
            ]);
        }
    }

    public function test_one_teacher_can_teach_two_subjects_in_routing(): void
    {
        $this->artisan('migrate');

        $matematika = Subject::where('name', 'Matematika')->first();
        $informatika = Subject::where('name', 'Informatika')->first();

        $this->assertNotNull($matematika);
        $this->assertNotNull($informatika);

        // Assign Guru Ahmad to Matematika in 7A
        $routing1 = TeacherSubjectClass::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $matematika->id,
            'classroom_id' => $this->class7A->id,
        ]);

        // Assign Guru Ahmad to Informatika in 7A & 7B
        $routing2 = TeacherSubjectClass::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $informatika->id,
            'classroom_id' => $this->class7A->id,
        ]);

        $routing3 = TeacherSubjectClass::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $informatika->id,
            'classroom_id' => $this->class7B->id,
        ]);

        $this->assertCount(3, $this->teacher->teacherSubjectClasses);
        $this->assertCount(2, $this->teacher->teachingSubjects);
        $this->assertTrue($this->teacher->teachingSubjects->contains('id', $matematika->id));
        $this->assertTrue($this->teacher->teachingSubjects->contains('id', $informatika->id));
    }

    public function test_teacher_can_enter_grades_per_subject_without_mixing(): void
    {
        $this->artisan('migrate');

        $matematika = Subject::where('name', 'Matematika')->first();
        $informatika = Subject::where('name', 'Informatika')->first();

        // Assign Guru Ahmad to Matematika and Informatika in 7A
        TeacherSubjectClass::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $matematika->id,
            'classroom_id' => $this->class7A->id,
        ]);
        TeacherSubjectClass::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $informatika->id,
            'classroom_id' => $this->class7A->id,
        ]);

        $student1 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->class7A->id,
            'name' => 'Fulan bin Fulan',
            'nis' => '1001',
            'tanggal_lahir' => '2012-05-10',
            'jenis_kelamin' => 'L',
        ]);

        $student2 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->class7A->id,
            'name' => 'Zaid bin Tsabit',
            'nis' => '1002',
            'tanggal_lahir' => '2012-08-15',
            'jenis_kelamin' => 'L',
        ]);

        $this->actingAs($this->teacher);

        // 1. Guru inputs Matematika grades
        Livewire::test(InputNilaiMapel::class, [
            'subject_id' => $matematika->id,
            'classroom_id' => $this->class7A->id,
        ])
        ->set("scores.{$student1->id}.pengetahuan", 85)
        ->set("scores.{$student1->id}.keterampilan", 90)
        ->set("scores.{$student1->id}.ujian", 88)
        ->set("scores.{$student2->id}.pengetahuan", 78)
        ->set("scores.{$student2->id}.keterampilan", 80)
        ->set("scores.{$student2->id}.ujian", 82)
        ->call('saveScores')
        ->assertHasNoErrors();

        // Verify Matematika grades saved
        $this->assertDatabaseHas('grades', [
            'student_id' => $student1->id,
            'subject_id' => $matematika->id,
            'type' => 'pengetahuan',
            'score' => 85,
        ]);
        $this->assertDatabaseHas('grades', [
            'student_id' => $student1->id,
            'subject_id' => $matematika->id,
            'type' => 'ujian',
            'score' => 88,
        ]);

        // 2. Guru switches to Informatika and inputs different grades
        Livewire::test(InputNilaiMapel::class, [
            'subject_id' => $informatika->id,
            'classroom_id' => $this->class7A->id,
        ])
        ->set("scores.{$student1->id}.pengetahuan", 95)
        ->set("scores.{$student1->id}.keterampilan", 98)
        ->set("scores.{$student1->id}.ujian", 92)
        ->call('saveScores')
        ->assertHasNoErrors();

        // Verify Informatika grades saved
        $this->assertDatabaseHas('grades', [
            'student_id' => $student1->id,
            'subject_id' => $informatika->id,
            'type' => 'pengetahuan',
            'score' => 95,
        ]);
        $this->assertDatabaseHas('grades', [
            'student_id' => $student1->id,
            'subject_id' => $informatika->id,
            'type' => 'ujian',
            'score' => 92,
        ]);

        // Verify Matematika grades are strictly unchanged and isolated
        $matematikaP = Grade::where([
            'student_id' => $student1->id,
            'subject_id' => $matematika->id,
            'type' => 'pengetahuan',
        ])->first();
        $this->assertEquals(85, $matematikaP->score);

        $informatikaP = Grade::where([
            'student_id' => $student1->id,
            'subject_id' => $informatika->id,
            'type' => 'pengetahuan',
        ])->first();
        $this->assertEquals(95, $informatikaP->score);
    }

    public function test_resources_navigation_and_access_for_kurikulum(): void
    {
        $this->actingAs($this->kurikulum);

        $this->assertTrue(SubjectResource::canAccess());
        $this->assertTrue(SubjectResource::shouldRegisterNavigation());

        $this->assertTrue(TeacherSubjectClassResource::canAccess());
        $this->assertTrue(TeacherSubjectClassResource::shouldRegisterNavigation());

        $this->assertTrue(GradeResource::canAccess());
        $this->assertTrue(GradeResource::shouldRegisterNavigation());

        $this->assertTrue(InputNilaiMapel::canAccess());
    }
}
