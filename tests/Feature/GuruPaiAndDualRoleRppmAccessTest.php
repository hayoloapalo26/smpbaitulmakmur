<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\AlurTujuanPembelajaranResource;
use App\Filament\Staff\Resources\CapaianPembelajaranResource;
use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\RppmResource;
use App\Filament\Staff\Resources\TujuanPembelajaranResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Rppm;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use App\Policies\RppmPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuruPaiAndDualRoleRppmAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected AcademicYear $academicYear;
    protected Classroom $classroom;
    protected Subject $mapelPai;
    protected Subject $mapelQuran;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_pai', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_tahfidz', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smp-baitul-makmur',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->classroom = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
        ]);

        $this->mapelPai = Subject::create([
            'name' => 'Pendidikan Agama Islam',
            'kode' => 'PAI',
            'institution_id' => $this->institution->id,
        ]);

        $this->mapelQuran = Subject::create([
            'name' => 'Al-Qur\'an (Tahfidz)',
            'kode' => 'QUR_TFZ',
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Guru PAI dengan role guru_mapel dapat mengakses RPPM & Perangkat Ajar.
     */
    public function test_guru_pai_with_guru_mapel_role_can_access_rppm(): void
    {
        $guruPai = User::create([
            'name' => 'Ustadz Ahmad Fauzi, S.Pd.I',
            'username' => 'guru_pai_1',
            'email' => 'ahmad.fauzi@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru PAI',
        ]);
        $guruPai->assignRole('guru_mapel');

        $this->assertTrue($guruPai->isSubjectTeacher());
        $this->assertFalse($guruPai->isPureQuranTeacher());

        $this->actingAs($guruPai);

        $this->assertTrue(RppmResource::canAccess());
        $this->assertTrue(RppmResource::shouldRegisterNavigation());
        $this->assertTrue(CapaianPembelajaranResource::canAccess());
        $this->assertTrue(TujuanPembelajaranResource::canAccess());
        $this->assertTrue(AlurTujuanPembelajaranResource::canAccess());

        $policy = new RppmPolicy();
        $this->assertTrue($policy->viewAny($guruPai));
        $this->assertTrue($policy->create($guruPai));
    }

    /**
     * Guru PAI yang memiliki DUAL ROLE (guru_alquran DAN guru_mapel)
     * TETAP DAPAT mengakses RPPM & Perangkat Ajar (tidak diblokir oleh isQuranTeacher).
     */
    public function test_guru_pai_with_dual_role_can_access_rppm(): void
    {
        $guruDual = User::create([
            'name' => 'Ustadz Mansur, M.Pd.I',
            'username' => 'guru_dual_role',
            'email' => 'mansur@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru PAI & Tahfidz',
        ]);
        $guruDual->assignRole(['guru_alquran', 'guru_mapel']);

        $this->assertTrue($guruDual->isQuranTeacher());
        $this->assertTrue($guruDual->isSubjectTeacher());
        $this->assertFalse($guruDual->isPureQuranTeacher());

        $this->actingAs($guruDual);

        $this->assertTrue(RppmResource::canAccess());
        $this->assertTrue(RppmResource::shouldRegisterNavigation());
        $this->assertTrue(CapaianPembelajaranResource::canAccess());
        $this->assertTrue(TujuanPembelajaranResource::canAccess());
        $this->assertTrue(AlurTujuanPembelajaranResource::canAccess());

        $policy = new RppmPolicy();
        $this->assertTrue($policy->viewAny($guruDual));
        $this->assertTrue($policy->create($guruDual));
    }

    /**
     * Guru yang hanya memiliki role guru_alquran, TETAPI ditugaskan mengampu mapel PAI
     * pada tabel Setting Guru Mapel (TeacherSubjectClass), otomatis diakui sebagai guru pengampu mapel
     * dan dapat mengakses fitur RPPM.
     */
    public function test_guru_with_quran_role_but_assigned_subject_in_setting_can_access_rppm(): void
    {
        $guruTsc = User::create([
            'name' => 'Ustadz Syamsuddin, S.Ag',
            'username' => 'guru_syamsuddin',
            'email' => 'syamsuddin@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru PAI',
        ]);
        $guruTsc->assignRole('guru_alquran');

        // Mapping di Setting Guru Mapel untuk Mapel PAI
        TeacherSubjectClass::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $guruTsc->id,
            'subject_id' => $this->mapelPai->id,
            'classroom_id' => $this->classroom->id,
        ]);

        $this->assertTrue($guruTsc->isSubjectTeacher());
        $this->assertFalse($guruTsc->isPureQuranTeacher());

        $this->actingAs($guruTsc);

        $this->assertTrue(RppmResource::canAccess());
        $this->assertTrue(RppmResource::shouldRegisterNavigation());

        $policy = new RppmPolicy();
        $this->assertTrue($policy->viewAny($guruTsc));
        $this->assertTrue($policy->create($guruTsc));
    }

    /**
     * Guru Al-Qur'an MURNI (hanya mengajar tahfidz/tahsin, tidak mengampu mapel sekolah apapun)
     * TIDAK memiliki akses RPPM dan menu RPPM tidak muncul di sidebar.
     */
    public function test_pure_quran_teacher_cannot_access_rppm(): void
    {
        $pureQuran = User::create([
            'name' => 'Ustadzah Fatimah, Al-Hafidzah',
            'username' => 'ustadzah_fatimah',
            'email' => 'fatimah.tahfidz@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru Tahfidz Al-Qur\'an',
        ]);
        $pureQuran->assignRole('guru_alquran');

        $this->assertTrue($pureQuran->isQuranTeacher());
        $this->assertFalse($pureQuran->isSubjectTeacher());
        $this->assertTrue($pureQuran->isPureQuranTeacher());

        $this->actingAs($pureQuran);

        $this->assertFalse(RppmResource::canAccess());
        $this->assertFalse(RppmResource::shouldRegisterNavigation());
        $this->assertFalse(CapaianPembelajaranResource::canAccess());
        $this->assertFalse(TujuanPembelajaranResource::canAccess());
        $this->assertFalse(AlurTujuanPembelajaranResource::canAccess());

        $policy = new RppmPolicy();
        $this->assertFalse($policy->viewAny($pureQuran));
        $this->assertFalse($policy->create($pureQuran));
    }

    /**
     * Kepala Sekolah dan Waka Kurikulum tetap memiliki hak akses penuh untuk supervisi & ACC RPPM.
     */
    public function test_kepala_sekolah_and_waka_kurikulum_supervision_access(): void
    {
        $kepsek = User::create([
            'name' => 'Kepala Sekolah',
            'username' => 'kepsek_test',
            'email' => 'kepsek@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $kepsek->assignRole('kepala_sekolah');

        $waka = User::create([
            'name' => 'Waka Kurikulum',
            'username' => 'waka_test',
            'email' => 'waka@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $waka->assignRole('waka_kurikulum');

        $this->actingAs($kepsek);
        $this->assertTrue(RppmResource::canAccess());
        $this->assertTrue(RppmResource::shouldRegisterNavigation());

        $this->actingAs($waka);
        $this->assertTrue(RppmResource::canAccess());
        $this->assertTrue(RppmResource::shouldRegisterNavigation());

        $policy = new RppmPolicy();
        $this->assertTrue($policy->viewAny($kepsek));
        $this->assertTrue($policy->viewAny($waka));
    }
}
