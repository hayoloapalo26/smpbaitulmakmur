<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Filament\Staff\Resources\ReadingQualityScoreResource;
use App\Filament\Staff\Resources\StudentQuranPlacementResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentQuranPlacementAndTeacherDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected AcademicYear $academicYear;
    protected Classroom $classroomA;
    protected Classroom $classroomB;

    protected User $wakaKurikulum;
    protected User $koordinatorMadin;
    protected User $guruYanbua;
    protected User $guruTahsin;
    protected User $guruTfzDasar;
    protected User $guruTfzLanjutan;
    protected User $guruBiasa;

    protected Student $santri1;
    protected Student $santri2;
    protected Student $santri3;
    protected Student $santri4;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        // 1. Roles
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'koordinator_madin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_yanbua', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahfidz_dasar', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahfidz_lanjutan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_kelas', 'guard_name' => 'web']);

        // 2. Data Induk
        $this->institution = Institution::create([
            'name' => 'SMP Baitul Makmur',
            'slug' => 'smp-baitul-makmur',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->classroomA = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'Kelas 7A',
            'tingkat' => '7',
            'grade_level' => 7,
        ]);

        $this->classroomB = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'Kelas 7B',
            'tingkat' => '7',
            'grade_level' => 7,
        ]);

        // 3. Users
        $this->wakaKurikulum = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ust. Waka Kurikulum',
            'email' => 'waka.kurikulum@smpbm.sch.id',
            'password' => bcrypt('password'),
            'has_account' => true,
        ]);
        $this->wakaKurikulum->assignRole('waka_kurikulum');

        $this->koordinatorMadin = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ust. Koordinator Madin',
            'email' => 'koordinator.madin@smpbm.sch.id',
            'password' => bcrypt('password'),
            'has_account' => true,
        ]);
        $this->koordinatorMadin->assignRole('koordinator_madin');

        $this->guruYanbua = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ustadzah Guru Yanbua',
            'email' => 'guru.yanbua@smpbm.sch.id',
            'password' => bcrypt('password'),
            'has_account' => true,
        ]);
        $this->guruYanbua->assignRole('guru_quran_yanbua');

        $this->guruTahsin = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ust. Guru Tahsin',
            'email' => 'guru.tahsin@smpbm.sch.id',
            'password' => bcrypt('password'),
            'has_account' => true,
        ]);
        $this->guruTahsin->assignRole('guru_quran_tahsin');

        $this->guruTfzDasar = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ust. Guru Tahfidz Dasar',
            'email' => 'guru.tfzdasar@smpbm.sch.id',
            'password' => bcrypt('password'),
            'has_account' => true,
        ]);
        $this->guruTfzDasar->assignRole('guru_quran_tahfidz_dasar');

        $this->guruTfzLanjutan = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ust. Guru Tahfidz Lanjutan',
            'email' => 'guru.tfzlanjutan@smpbm.sch.id',
            'password' => bcrypt('password'),
            'has_account' => true,
        ]);
        $this->guruTfzLanjutan->assignRole('guru_quran_tahfidz_lanjutan');

        $this->guruBiasa = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Guru Biasa Non-Quran',
            'email' => 'guru.biasa@smpbm.sch.id',
            'password' => bcrypt('password'),
            'has_account' => true,
        ]);
        $this->guruBiasa->assignRole('guru_kelas');

        // 4. Students
        $this->santri1 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroomA->id,
            'name' => 'Ahmad Fulan Yanbua',
            'nis' => '1001',
            'nisn' => '001001001',
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
        ]);

        $this->santri2 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroomA->id,
            'name' => 'Budi Santoso Tahfidz Dasar',
            'nis' => '1002',
            'nisn' => '001001002',
            'tanggal_lahir' => '2012-02-02',
            'jenis_kelamin' => 'L',
        ]);

        $this->santri3 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroomB->id,
            'name' => 'Citra Lestari Tahfidz Lanjutan',
            'nis' => '1003',
            'nisn' => '001001003',
            'tanggal_lahir' => '2012-03-03',
            'jenis_kelamin' => 'P',
        ]);

        $this->santri4 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroomB->id,
            'name' => 'Dina Amalia Tahsin',
            'nis' => '1004',
            'nisn' => '001001004',
            'tanggal_lahir' => '2012-04-04',
            'jenis_kelamin' => 'P',
        ]);
    }

    public function test_students_table_has_quran_placement_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('students', 'kelas_quran'));
        $this->assertTrue(Schema::hasColumn('students', 'guru_quran_id'));
        $this->assertTrue(Schema::hasColumn('students', 'tanggal_penempatan_quran'));
        $this->assertTrue(Schema::hasColumn('students', 'catatan_penempatan_quran'));
    }

    public function test_student_and_user_relationships_for_quran_placement(): void
    {
        $this->santri1->update([
            'kelas_quran' => 'yanbua',
            'guru_quran_id' => $this->guruYanbua->id,
            'tanggal_penempatan_quran' => '2026-10-29',
            'catatan_penempatan_quran' => 'Penempatan Jilid 2',
        ]);

        $this->santri1->refresh();
        $this->assertEquals('Yanbu\'a', $this->santri1->kelas_quran_label);
        $this->assertNotNull($this->santri1->guruQuran);
        $this->assertEquals($this->guruYanbua->id, $this->santri1->guruQuran->id);

        $this->assertTrue($this->guruYanbua->santriQuran->contains($this->santri1));
    }

    public function test_authorization_for_student_quran_placement_resource(): void
    {
        $this->actingAs($this->wakaKurikulum);
        $this->assertTrue(StudentQuranPlacementResource::canAccess());
        $this->assertTrue(StudentQuranPlacementResource::shouldRegisterNavigation());

        $this->actingAs($this->koordinatorMadin);
        $this->assertTrue(StudentQuranPlacementResource::canAccess());

        $this->actingAs($this->guruBiasa);
        $this->assertFalse(StudentQuranPlacementResource::canAccess());
        $this->assertFalse(StudentQuranPlacementResource::shouldRegisterNavigation());
    }

    public function test_waka_kurikulum_can_place_students_and_guru_dropdown_automatically_filters(): void
    {
        // 1. Waka Kurikulum places Ahmad to Yanbu'a under guruYanbua
        $this->santri1->update([
            'kelas_quran' => 'yanbua',
            'guru_quran_id' => $this->guruYanbua->id,
            'tanggal_penempatan_quran' => '2026-10-29',
        ]);

        // 2. Waka Kurikulum places Budi to Tahfidz Dasar under guruTfzDasar
        $this->santri2->update([
            'kelas_quran' => 'tahfidz_dasar',
            'guru_quran_id' => $this->guruTfzDasar->id,
            'tanggal_penempatan_quran' => '2026-10-29',
        ]);

        // 3. Test form student dropdown for Guru Yanbu'a in QuranAchievementResource
        $this->actingAs($this->guruYanbua);
        $livewire = Livewire::test(QuranAchievementResource\Pages\ManageQuranAchievements::class);
        $livewire->mountAction('create');
        $form = $livewire->instance()->getMountedActionForm();
        $studentComponent = collect($form->getFlatComponents())->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'student_id');
        $options = $studentComponent?->getOptions() ?? [];

        // Ahmad Fulan (placed under guruYanbua) must be available in the dropdown
        $this->assertArrayHasKey($this->santri1->id, $options);
        // Budi Santoso (placed under guruTfzDasar) must NOT be present
        $this->assertArrayNotHasKey($this->santri2->id, $options);

        // 4. Test form student dropdown for Guru Tahfidz Dasar in ReadingQualityScoreResource
        $this->actingAs($this->guruTfzDasar);
        $livewireTfz = Livewire::test(ReadingQualityScoreResource\Pages\ManageReadingQualityScores::class);
        $livewireTfz->mountAction('create');
        $formTfz = $livewireTfz->instance()->getMountedActionForm();
        $studentComponentTfz = collect($formTfz->getFlatComponents())->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'student_id');
        $optionsTfz = $studentComponentTfz?->getOptions() ?? [];

        $this->assertArrayHasKey($this->santri2->id, $optionsTfz);
        $this->assertArrayNotHasKey($this->santri1->id, $optionsTfz);
    }

    public function test_bulk_placement_and_reset_workflow(): void
    {
        $this->actingAs($this->wakaKurikulum);

        // Place santri 3 and 4 in bulk
        Livewire::test(StudentQuranPlacementResource\Pages\ManageStudentQuranPlacements::class)
            ->callTableBulkAction('bulk_tempatkan', [$this->santri3, $this->santri4], [
                'kelas_quran' => 'tahsin',
                'guru_quran_id' => $this->guruTahsin->id,
                'tanggal_penempatan_quran' => '2026-10-29',
                'catatan_penempatan_quran' => 'Penempatan massal Tahsin',
            ])
            ->assertHasNoTableActionErrors();

        $this->santri3->refresh();
        $this->santri4->refresh();

        $this->assertEquals('tahsin', $this->santri3->kelas_quran);
        $this->assertEquals($this->guruTahsin->id, $this->santri3->guru_quran_id);
        $this->assertEquals('tahsin', $this->santri4->kelas_quran);
        $this->assertEquals($this->guruTahsin->id, $this->santri4->guru_quran_id);

        // Reset santri 3
        Livewire::test(StudentQuranPlacementResource\Pages\ManageStudentQuranPlacements::class)
            ->callTableAction('reset_penempatan', $this->santri3)
            ->assertHasNoTableActionErrors();

        $this->santri3->refresh();
        $this->assertNull($this->santri3->kelas_quran);
        $this->assertNull($this->santri3->guru_quran_id);
    }

    public function test_tahsin_class_filtering_and_quran_class_label_in_student_dropdown(): void
    {
        // 1. Place santri 4 into Tahsin under guruTahsin
        $this->santri4->update([
            'kelas_quran' => 'tahsin',
            'guru_quran_id' => $this->guruTahsin->id,
            'tanggal_penempatan_quran' => '2026-10-29',
        ]);

        // 2. Place santri 1 into Yanbua under guruYanbua
        $this->santri1->update([
            'kelas_quran' => 'yanbua',
            'guru_quran_id' => $this->guruYanbua->id,
            'tanggal_penempatan_quran' => '2026-10-29',
        ]);

        $this->actingAs($this->guruTahsin);

        // Test in ReadingQualityScoreResource: when guruTahsin opens the create action
        $livewire = Livewire::test(ReadingQualityScoreResource\Pages\ManageReadingQualityScores::class);
        $livewire->mountAction('create');
        $form = $livewire->instance()->getMountedActionForm();
        $studentComponent = collect($form->getFlatComponents())->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'student_id');

        $options = $studentComponent?->getOptions() ?? [];

        // Santri 4 (Tahsin) MUST be present and have the Tahsin label
        $this->assertArrayHasKey($this->santri4->id, $options);
        $this->assertStringContainsString('Kelas Al-Qur\'an: Tahsin', $options[$this->santri4->id]);

        // Santri 1 (Yanbu'a) must NOT be present for Guru Tahsin
        $this->assertArrayNotHasKey($this->santri1->id, $options);
    }
}
