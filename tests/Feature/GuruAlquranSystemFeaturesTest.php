<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Filament\Staff\Resources\ReadingQualityScoreResource;
use App\Filament\Staff\Pages\TahfidzRanking;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuruAlquranSystemFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $superadmin;
    protected User $guruAlquran;
    protected Classroom $class7A;
    protected Classroom $class8A;
    protected Student $student1;
    protected Student $student2;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@smp.test',
            'username' => 'superadmin',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->superadmin->assignRole('superadmin');

        $this->class7A = Classroom::create([
            'name' => '7A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
        ]);
        $this->class8A = Classroom::create([
            'name' => '8A',
            'tingkat' => '8',
            'institution_id' => $this->institution->id,
        ]);

        $this->student1 = Student::create([
            'name' => 'Ahmad Faiz',
            'nis' => '1001',
            'nisn' => '0012345678',
            'tanggal_lahir' => '2010-01-01',
            'jenis_kelamin' => 'L',
            'classroom_id' => $this->class7A->id,
            'institution_id' => $this->institution->id,
        ]);
        $this->student2 = Student::create([
            'name' => 'Budi Santoso',
            'nis' => '1002',
            'nisn' => '0012345679',
            'tanggal_lahir' => '2010-02-02',
            'jenis_kelamin' => 'L',
            'classroom_id' => $this->class8A->id,
            'institution_id' => $this->institution->id,
        ]);

        $this->guruAlquran = User::create([
            'name' => 'Ust. Lukman Hakim, S.Th.I',
            'email' => 'lukman@guru.test',
            'username' => 'lukman_quran',
            'password' => bcrypt('guru123'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru Al-Qur\'an',
            'has_account' => true,
            'is_active' => true,
        ]);
        $this->guruAlquran->assignRole('guru_alquran');
    }

    public function test_guru_alquran_has_navigation_and_access_to_quran_features(): void
    {
        $this->actingAs($this->guruAlquran);

        $this->assertTrue(QuranAchievementResource::canAccess());
        $this->assertTrue(QuranAchievementResource::shouldRegisterNavigation());
        $this->assertEquals('Fitur Guru Al-Qur\'an', QuranAchievementResource::getNavigationGroup());

        $this->assertTrue(ReadingQualityScoreResource::canAccess());
        $this->assertTrue(ReadingQualityScoreResource::shouldRegisterNavigation());
        $this->assertEquals('Fitur Guru Al-Qur\'an', ReadingQualityScoreResource::getNavigationGroup());

        $this->assertTrue(TahfidzRanking::canAccess());
        $this->assertTrue(TahfidzRanking::shouldRegisterNavigation());
        $this->assertEquals('Fitur Guru Al-Qur\'an', TahfidzRanking::getNavigationGroup());
    }

    public function test_guru_alquran_can_record_quran_achievement_and_reading_score(): void
    {
        $this->actingAs($this->guruAlquran);

        $achievement = QuranAchievement::create([
            'student_id' => $this->student1->id,
            'teacher_id' => $this->guruAlquran->id,
            'type' => 'ziyadah',
            'juz' => 30,
            'surah' => 'An-Naba',
            'ayah' => '1-10',
            'catatan' => 'Lancar dan makhraj tepat',
            'tanggal' => Carbon::today()->format('Y-m-d'),
        ]);

        $this->assertDatabaseHas('quran_achievements', [
            'id' => $achievement->id,
            'student_id' => $this->student1->id,
            'teacher_id' => $this->guruAlquran->id,
        ]);

        $score = ReadingQualityScore::create([
            'student_id' => $this->student1->id,
            'teacher_id' => $this->guruAlquran->id,
            'program' => 'tahfidz',
            'scale_score' => 90,
            'tanggal' => Carbon::today()->format('Y-m-d'),
        ]);

        $this->assertDatabaseHas('reading_quality_scores', [
            'id' => $score->id,
            'student_id' => $this->student1->id,
            'teacher_id' => $this->guruAlquran->id,
            'scale_score' => 90,
        ]);
    }

    public function test_tahfidz_ranking_displays_classroom_ziyadah_counts(): void
    {
        $this->actingAs($this->guruAlquran);

        QuranAchievement::create([
            'student_id' => $this->student1->id,
            'teacher_id' => $this->guruAlquran->id,
            'type' => 'ziyadah',
            'juz' => 30,
            'surah' => 'An-Nas',
            'ayah' => '1-6',
            'tanggal' => Carbon::today()->format('Y-m-d'),
        ]);

        QuranAchievement::create([
            'student_id' => $this->student1->id,
            'teacher_id' => $this->guruAlquran->id,
            'type' => 'ziyadah',
            'juz' => 30,
            'surah' => 'Al-Falaq',
            'ayah' => '1-5',
            'tanggal' => Carbon::today()->format('Y-m-d'),
        ]);

        $page = new TahfidzRanking();
        $refMethod = new \ReflectionMethod(TahfidzRanking::class, 'getViewData');
        $refMethod->setAccessible(true);
        $data = $refMethod->invoke($page);

        $this->assertArrayHasKey('rankings', $data);
        $this->assertArrayHasKey('7A', $data['rankings']);
        $this->assertEquals(2, $data['rankings']['7A']->first()->ziyadah_count);
    }

    public function test_guru_alquran_does_not_access_general_grade_resource(): void
    {
        $this->actingAs($this->guruAlquran);
        // Khusus guru Al-Qur'an, tidak ada inputan CP, TP, ATP, dan Nilai Mapel umum
        $this->assertFalse(GradeResource::canAccess());
        $this->assertFalse(GradeResource::shouldRegisterNavigation());
    }
}
