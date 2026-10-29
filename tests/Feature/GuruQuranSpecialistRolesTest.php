<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\InputNilaiMapel;
use App\Filament\Staff\Pages\PenerimaanNilaiMadin;
use App\Filament\Staff\Pages\TahfidzRanking;
use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Filament\Staff\Resources\ReadingQualityScoreResource;
use App\Filament\Staff\Resources\RppmResource;
use App\Filament\Staff\Resources\ScheduleResource;
use App\Filament\Staff\Resources\TeacherSubjectClassResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuruQuranSpecialistRolesTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected AcademicYear $academicYear;
    protected Classroom $classroom;
    protected Student $student;

    protected User $wakaKurikulum;
    protected User $koordinatorMadin;
    protected User $guruYanbua;
    protected User $guruTahsin;
    protected User $guruTfzDasar;
    protected User $guruTfzLanjutan;

    protected Subject $mapelYanbua;
    protected Subject $mapelTahsin;
    protected Subject $mapelTfzDasar;
    protected Subject $mapelTfzLanjutan;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        // 1. Roles
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'koordinator_madin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_yanbua', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahfidz_dasar', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahfidz_lanjutan', 'guard_name' => 'web']);

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

        // 3. User Waka Kurikulum & Koordinator Madin
        $this->wakaKurikulum = User::create([
            'name' => 'Waka Kurikulum Nadila',
            'username' => 'waka_nadila',
            'email' => 'waka.kurikulum@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Waka Kurikulum',
        ]);
        $this->wakaKurikulum->assignRole('waka_kurikulum');

        $this->koordinatorMadin = User::create([
            'name' => 'Ust. Elly Koordinator Madin',
            'username' => 'elly_madin',
            'email' => 'elly.madin@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Koordinator Madin',
        ]);
        $this->koordinatorMadin->assignRole('koordinator_madin');

        // 4. User 4 Guru Spesialis Al-Qur'an
        $this->guruYanbua = User::create([
            'name' => 'Ust. Zaenal Yanbua',
            'username' => 'zaenal_yanbua',
            'email' => 'zaenal.yanbua@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru Yanbua',
        ]);
        $this->guruYanbua->assignRole('guru_quran_yanbua');

        $this->guruTahsin = User::create([
            'name' => 'Ust. Chisbulloh Tahsin',
            'username' => 'chisbulloh_tahsin',
            'email' => 'chisbulloh.tahsin@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru Tahsin',
        ]);
        $this->guruTahsin->assignRole('guru_quran_tahsin');

        $this->guruTfzDasar = User::create([
            'name' => 'Ust. Romli Tahfidz Dasar',
            'username' => 'romli_tfzdasar',
            'email' => 'romli.tfzdasar@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru Tahfidz Dasar',
        ]);
        $this->guruTfzDasar->assignRole('guru_quran_tahfidz_dasar');

        $this->guruTfzLanjutan = User::create([
            'name' => 'Ust. Lukman Tahfidz Lanjutan',
            'username' => 'lukman_tfzlanjutan',
            'email' => 'lukman.tfzlanjutan@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru Tahfidz Lanjutan',
        ]);
        $this->guruTfzLanjutan->assignRole('guru_quran_tahfidz_lanjutan');

        // 5. Classroom & Student
        $this->classroom = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
            'wali_kelas_id' => $this->koordinatorMadin->id,
        ]);

        $this->student = Student::create([
            'name' => 'Ahmad Santri',
            'nis' => '1001',
            'nisn' => '0011223344',
            'classroom_id' => $this->classroom->id,
            'institution_id' => $this->institution->id,
            'tanggal_lahir' => '2012-05-10',
            'tempat_lahir' => 'Malang',
            'jenis_kelamin' => 'L',
        ]);

        // 5. Subjects Al-Qur'an Spesialis
        $this->mapelYanbua = Subject::create([
            'name' => 'Al-Qur\'an (Yanbu\'a)',
            'kode' => 'QUR_YANBUA',
            'institution_id' => $this->institution->id,
        ]);
        $this->mapelTahsin = Subject::create([
            'name' => 'Al-Qur\'an (Tahsin)',
            'kode' => 'QUR_TAHSIN',
            'institution_id' => $this->institution->id,
        ]);
        $this->mapelTfzDasar = Subject::create([
            'name' => 'Al-Qur\'an (Tahfidz Dasar)',
            'kode' => 'QUR_TFZ_DASAR',
            'institution_id' => $this->institution->id,
        ]);
        $this->mapelTfzLanjutan = Subject::create([
            'name' => 'Al-Qur\'an (Tahfidz Lanjutan)',
            'kode' => 'QUR_TFZ_LANJUTAN',
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 1: 4 Role baru guru Al-Qur'an memiliki akses ke seluruh fitur Al-Qur'an & Kurikulum
     */
    public function test_all_4_quran_teacher_roles_have_access_to_relevant_features(): void
    {
        $teachers = [
            $this->guruYanbua,
            $this->guruTahsin,
            $this->guruTfzDasar,
            $this->guruTfzLanjutan,
        ];

        foreach ($teachers as $teacher) {
            $this->actingAs($teacher);

            $this->assertTrue(QuranAchievementResource::canAccess());
            $this->assertTrue(ReadingQualityScoreResource::canAccess());
            $this->assertTrue(TahfidzRanking::canAccess());
            $this->assertTrue(InputNilaiMapel::canAccess());
            $this->assertTrue(GradeResource::canAccess());
            $this->assertTrue(ScheduleResource::canAccess());
            $this->assertTrue(RppmResource::canAccess());

            $this->assertTrue($teacher->isQuranTeacher());
            $this->assertNotNull($teacher->getQuranSpecializationLabel());
        }

        $this->assertEquals('Yanbu\'a', $this->guruYanbua->getQuranSpecializationLabel());
        $this->assertEquals('Tahsin', $this->guruTahsin->getQuranSpecializationLabel());
        $this->assertEquals('Tahfidz Dasar', $this->guruTfzDasar->getQuranSpecializationLabel());
        $this->assertEquals('Tahfidz Lanjutan', $this->guruTfzLanjutan->getQuranSpecializationLabel());
    }

    /**
     * Test 2: Waka Kurikulum dapat melakukan routing penugasan mengajar untuk ke-4 guru Al-Qur'an
     */
    public function test_waka_kurikulum_can_route_all_4_quran_teachers(): void
    {
        $this->actingAs($this->wakaKurikulum);
        $this->assertTrue(TeacherSubjectClassResource::canAccess());

        // Routing 1: Guru Yanbua -> Mapel Yanbua di Kelas VII-A
        $routing1 = TeacherSubjectClass::create([
            'teacher_id' => $this->guruYanbua->id,
            'subject_id' => $this->mapelYanbua->id,
            'classroom_id' => $this->classroom->id,
        ]);

        // Routing 2: Guru Tahsin -> Mapel Tahsin di Kelas VII-A
        $routing2 = TeacherSubjectClass::create([
            'teacher_id' => $this->guruTahsin->id,
            'subject_id' => $this->mapelTahsin->id,
            'classroom_id' => $this->classroom->id,
        ]);

        // Routing 3: Guru Tahfidz Dasar -> Mapel Tahfidz Dasar di Kelas VII-A
        $routing3 = TeacherSubjectClass::create([
            'teacher_id' => $this->guruTfzDasar->id,
            'subject_id' => $this->mapelTfzDasar->id,
            'classroom_id' => $this->classroom->id,
        ]);

        // Routing 4: Guru Tahfidz Lanjutan -> Mapel Tahfidz Lanjutan di Kelas VII-A
        $routing4 = TeacherSubjectClass::create([
            'teacher_id' => $this->guruTfzLanjutan->id,
            'subject_id' => $this->mapelTfzLanjutan->id,
            'classroom_id' => $this->classroom->id,
        ]);

        $this->assertDatabaseHas('teacher_subject_classes', ['id' => $routing1->id]);
        $this->assertDatabaseHas('teacher_subject_classes', ['id' => $routing2->id]);
        $this->assertDatabaseHas('teacher_subject_classes', ['id' => $routing3->id]);
        $this->assertDatabaseHas('teacher_subject_classes', ['id' => $routing4->id]);
    }

    /**
     * Test 3: Seluruh inputan nilai & capaian dari ke-4 guru Al-Qur'an muncul di Koordinator Madin
     */
    public function test_all_inputted_grades_from_4_teachers_appear_in_koordinator_madin(): void
    {
        // 1. Guru Yanbua input setoran hafalan
        $achYanbua = QuranAchievement::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->guruYanbua->id,
            'tanggal' => now()->toDateString(),
            'type' => 'ziyadah',
            'juz' => 30,
            'surah' => '78',
            'ayah' => '1-10',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        // 2. Guru Tahsin input skor kualitas bacaan
        $rqsTahsin = ReadingQualityScore::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->guruTahsin->id,
            'tanggal' => now()->toDateString(),
            'program' => 'tahsin',
            'makharijul_huruf' => 4,
            'shifatul_huruf' => 4,
            'ahkamul_mad' => 4,
            'ahkamul_huruf' => 4,
            'kelancaran' => 4,
            'scale_score' => 88,
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        // 3. Guru Tahfidz Dasar input nilai mapel
        $gradeTfzDasar = Grade::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->guruTfzDasar->id,
            'subject_id' => $this->mapelTfzDasar->id,
            'classroom_id' => $this->classroom->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'formatif',
            'score' => 92,
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        // 4. Guru Tahfidz Lanjutan input nilai mapel
        $gradeTfzLanjutan = Grade::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->guruTfzLanjutan->id,
            'subject_id' => $this->mapelTfzLanjutan->id,
            'classroom_id' => $this->classroom->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'formatif',
            'score' => 95,
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        // 5. Koordinator Madin membuka halaman Penerimaan Nilai Madin
        $this->actingAs($this->koordinatorMadin);

        $badge = PenerimaanNilaiMadin::getNavigationBadge();
        $this->assertEquals('4', $badge);

        Livewire::test(PenerimaanNilaiMadin::class)
            ->set('activeTab', 'capaian_quran')
            ->assertSee('Ahmad Santri')
            ->assertSee('Ust. Zaenal Yanbua')
            ->set('activeTab', 'kualitas_bacaan')
            ->assertSee('Ust. Chisbulloh Tahsin')
            ->assertSee('88')
            ->set('activeTab', 'nilai_mapel')
            ->assertSee('Ust. Romli Tahfidz Dasar')
            ->assertSee('Ust. Lukman Tahfidz Lanjutan')
            ->set('activeTab', 'monitoring_guru')
            ->assertSee('Ust. Zaenal Yanbua')
            ->assertSee('Yanbu\'a')
            ->assertSee('Ust. Chisbulloh Tahsin')
            ->assertSee('Tahsin')
            ->assertSee('Ust. Romli Tahfidz Dasar')
            ->assertSee('Tahfidz Dasar')
            ->assertSee('Ust. Lukman Tahfidz Lanjutan')
            ->assertSee('Tahfidz Lanjutan');
    }

    /**
     * Test 4: Koordinator Madin dapat memfilter berdasarkan spesialisasi guru Al-Qur'an dan memverifikasi
     */
    public function test_koordinator_madin_can_filter_by_specialization_and_verify(): void
    {
        // Buat inputan dari Guru Yanbua & Tahsin
        $achYanbua = QuranAchievement::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->guruYanbua->id,
            'tanggal' => now()->toDateString(),
            'type' => 'ziyadah',
            'juz' => 30,
            'surah' => '78',
            'ayah' => '1-10',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        $rqsTahsin = ReadingQualityScore::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->guruTahsin->id,
            'tanggal' => now()->toDateString(),
            'program' => 'tahsin',
            'scale_score' => 90,
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        $this->actingAs($this->koordinatorMadin);

        // Aksi 1: Filter spesialisasi Yanbua -> menerima seluruh inputan Guru Yanbua secara massal
        Livewire::test(PenerimaanNilaiMadin::class)
            ->set('selectedSpecialization', 'yanbua')
            ->call('terimaSemuaDariGuru', $this->guruYanbua->id);

        $this->assertEquals('Diterima', $achYanbua->fresh()->status_verifikasi);
        $this->assertEquals($this->koordinatorMadin->id, $achYanbua->fresh()->verified_by);

        // Aksi 2: Terima Capaian Kualitas Tahsin secara individual
        Livewire::test(PenerimaanNilaiMadin::class)
            ->call('terimaKualitas', $rqsTahsin->id);

        $this->assertEquals('Diterima', $rqsTahsin->fresh()->status_verifikasi);
        $this->assertEquals($this->koordinatorMadin->id, $rqsTahsin->fresh()->verified_by);
    }
}
