<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\CharacterScoreReport;
use App\Filament\Staff\Pages\InputNilaiMapel;
use App\Filament\Staff\Pages\PenerimaanNilaiMadin;
use App\Filament\Staff\Pages\RekapNilaiKelas;
use App\Filament\Staff\Pages\TahfidzRanking;
use App\Filament\Staff\Resources\AlurTujuanPembelajaranResource;
use App\Filament\Staff\Resources\BkWorkProgramResource;
use App\Filament\Staff\Resources\CapaianPembelajaranResource;
use App\Filament\Staff\Resources\ClassProgramResource;
use App\Filament\Staff\Resources\DutyScheduleResource;
use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\InventoryResource;
use App\Filament\Staff\Resources\LearningPlanResource;
use App\Filament\Staff\Resources\ParentSummonResource;
use App\Filament\Staff\Resources\PresensiIzinSiswaResource;
use App\Filament\Staff\Resources\ProgramEvaluationResource;
use App\Filament\Staff\Resources\ProgramHumasResource;
use App\Filament\Staff\Resources\ProgramSarprasResource;
use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Filament\Staff\Resources\ReadingQualityScoreResource;
use App\Filament\Staff\Resources\RppmResource;
use App\Filament\Staff\Resources\ScheduleResource;
use App\Filament\Staff\Resources\StudentAchievementResource;
use App\Filament\Staff\Resources\StudentCaseResource;
use App\Filament\Staff\Resources\StudentQuranPlacementResource;
use App\Filament\Staff\Resources\StudentResource;
use App\Filament\Staff\Resources\SubjectResource;
use App\Filament\Staff\Resources\TeacherLeaveRequestResource;
use App\Filament\Staff\Resources\TeacherProfileResource;
use App\Filament\Staff\Resources\TeacherSubjectClassResource;
use App\Filament\Staff\Resources\TeachingJournalResource;
use App\Filament\Staff\Resources\TujuanPembelajaranResource;
use App\Models\Institution;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleBasedNavigationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guruQuran;
    protected User $guruYanbua;
    protected User $guruTahsin;
    protected User $guruMapel;
    protected User $koordinatorMadin;
    protected User $guruBk;
    protected User $petugasSarpras;
    protected User $petugasHumas;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tata_usaha', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_yanbua', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahfidz_dasar', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahfidz_lanjutan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'koordinator_madin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bk', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sarpras', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'humas', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name' => 'SMP Baitul Makmur',
            'slug' => 'smp-baitul-makmur',
        ]);

        $this->guruQuran = User::create([
            'name' => 'Ust. Quran Murni',
            'username' => 'quran_murni',
            'email' => 'quran.murni@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->guruQuran->assignRole('guru_alquran');

        $this->guruYanbua = User::create([
            'name' => 'Ust. Yanbua Spesialis',
            'username' => 'yanbua_spesialis',
            'email' => 'yanbua.spesialis@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->guruYanbua->assignRole('guru_quran_yanbua');

        $this->guruTahsin = User::create([
            'name' => 'Ust. Tahsin Spesialis',
            'username' => 'tahsin_spesialis',
            'email' => 'tahsin.spesialis@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->guruTahsin->assignRole('guru_quran_tahsin');

        $this->guruMapel = User::create([
            'name' => 'Guru Matematika Mapel',
            'username' => 'guru_mtk',
            'email' => 'mtk@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->guruMapel->assignRole('guru_mapel');

        $this->koordinatorMadin = User::create([
            'name' => 'Ust. Koordinator Madin',
            'username' => 'koord_madin',
            'email' => 'koord.madin@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->koordinatorMadin->assignRole('koordinator_madin');

        $this->guruBk = User::create([
            'name' => 'Guru BK',
            'username' => 'guru_bk',
            'email' => 'bk@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->guruBk->assignRole('bk');

        $this->petugasSarpras = User::create([
            'name' => 'Petugas Sarpras',
            'username' => 'sarpras_user',
            'email' => 'sarpras@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->petugasSarpras->assignRole('sarpras');

        $this->petugasHumas = User::create([
            'name' => 'Petugas Humas',
            'username' => 'humas_user',
            'email' => 'humas@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->petugasHumas->assignRole('humas');
    }

    /**
     * Test: Guru Al-Qur'an (umum & spesialis) TIDAK dapat mengakses kurikulum mapel umum
     */
    public function test_quran_teachers_cannot_access_general_mapel_curriculum_features(): void
    {
        $quranTeachers = [$this->guruQuran, $this->guruYanbua, $this->guruTahsin];

        foreach ($quranTeachers as $teacher) {
            $this->actingAs($teacher);

            // Fitur Kurikulum Mapel Umum: NONAKTIF untuk Guru Al-Qur'an
            $this->assertFalse(GradeResource::canAccess());
            $this->assertFalse(GradeResource::shouldRegisterNavigation());
            $this->assertFalse(InputNilaiMapel::canAccess());
            $this->assertFalse(InputNilaiMapel::shouldRegisterNavigation());
            $this->assertFalse(CapaianPembelajaranResource::canAccess());
            $this->assertFalse(TujuanPembelajaranResource::canAccess());
            $this->assertFalse(AlurTujuanPembelajaranResource::canAccess());
            $this->assertFalse(RppmResource::canAccess());
            $this->assertFalse(RppmResource::shouldRegisterNavigation());
            $this->assertFalse(LearningPlanResource::canAccess());
            $this->assertFalse(TeacherSubjectClassResource::canAccess());
            $this->assertFalse(SubjectResource::canAccess());

            // Fitur Non-Tugas: Jadwal Piket tidak muncul saat tidak bertugas
            $this->assertFalse(DutyScheduleResource::shouldRegisterNavigation());

            // Fitur Al-Qur'an & Tugas Guru: AKTIF
            $this->assertTrue(QuranAchievementResource::canAccess());
            $this->assertTrue(ReadingQualityScoreResource::canAccess());
            $this->assertTrue(TahfidzRanking::canAccess());
            $this->assertTrue(TeachingJournalResource::canAccess());
            $this->assertTrue(ScheduleResource::canAccess());
            $this->assertTrue(TeacherLeaveRequestResource::canAccess());
        }
    }

    /**
     * Test: Guru Mapel TIDAK melihat fitur khusus Al-Qur'an, BK, Sarpras, dan Humas
     */
    public function test_mapel_teachers_only_see_their_relevant_features(): void
    {
        $this->actingAs($this->guruMapel);

        // Fitur Al-Qur'an khusus: NONAKTIF untuk Guru Mapel umum
        $this->assertFalse(QuranAchievementResource::canAccess());
        $this->assertFalse(ReadingQualityScoreResource::canAccess());
        $this->assertFalse(TahfidzRanking::canAccess());
        $this->assertFalse(PenerimaanNilaiMadin::canAccess());
        $this->assertFalse(StudentQuranPlacementResource::canAccess());

        // Fitur Bidang Lain: NONAKTIF
        $this->assertFalse(BkWorkProgramResource::canAccess());
        $this->assertFalse(InventoryResource::canAccess());
        $this->assertFalse(ProgramHumasResource::canAccess());

        // Fitur Kurikulum Mapel: AKTIF
        $this->assertTrue(GradeResource::canAccess());
        $this->assertTrue(GradeResource::shouldRegisterNavigation());
        $this->assertTrue(InputNilaiMapel::canAccess());
        $this->assertTrue(InputNilaiMapel::shouldRegisterNavigation());
        $this->assertTrue(RppmResource::canAccess());
        $this->assertTrue(TeachingJournalResource::canAccess());
    }

    /**
     * Test: Koordinator Madin hanya melihat fitur Madin & Al-Qur'an, BUKAN input nilai mapel umum
     */
    public function test_koordinator_madin_does_not_see_generic_mapel_grade_inputs(): void
    {
        $this->actingAs($this->koordinatorMadin);

        // Input Nilai Mapel umum: NONAKTIF
        $this->assertFalse(InputNilaiMapel::canAccess());
        $this->assertFalse(GradeResource::shouldRegisterNavigation());
        $this->assertFalse(RppmResource::canAccess());

        // Fitur Madin & Al-Qur'an: AKTIF
        $this->assertTrue(PenerimaanNilaiMadin::canAccess());
        $this->assertTrue(QuranAchievementResource::canAccess());
        $this->assertTrue(ReadingQualityScoreResource::canAccess());
        $this->assertTrue(TahfidzRanking::canAccess());
        $this->assertTrue(StudentQuranPlacementResource::canAccess());
    }

    /**
     * Test: Petugas Sarpras & Humas terisolasi sesuai perannya
     */
    public function test_sarpras_and_humas_are_properly_isolated(): void
    {
        $this->actingAs($this->petugasSarpras);
        $this->assertTrue(InventoryResource::canAccess());
        $this->assertTrue(ProgramSarprasResource::canAccess());
        $this->assertFalse(ProgramHumasResource::canAccess());
        $this->assertFalse(GradeResource::canAccess());
        $this->assertFalse(QuranAchievementResource::canAccess());

        $this->actingAs($this->petugasHumas);
        $this->assertTrue(ProgramHumasResource::canAccess());
        $this->assertFalse(InventoryResource::canAccess());
        $this->assertFalse(ProgramSarprasResource::canAccess());
        $this->assertFalse(GradeResource::canAccess());
        $this->assertFalse(QuranAchievementResource::canAccess());
    }

    /**
     * Test: Master Data Guru hanya dapat diakses oleh TU, Kepsek, Waka, dan Superadmin (Bukan guru biasa)
     */
    public function test_teacher_profiles_resource_is_strictly_limited_to_management_and_tu(): void
    {
        // Guru biasa (Quran, Mapel, Humas, Sarpras) TIDAK BISA melihat / mengakses master Data Guru
        $this->actingAs($this->guruQuran);
        $this->assertFalse(TeacherProfileResource::canAccess());
        $this->assertFalse(TeacherProfileResource::shouldRegisterNavigation());

        $this->actingAs($this->guruMapel);
        $this->assertFalse(TeacherProfileResource::canAccess());
        $this->assertFalse(TeacherProfileResource::shouldRegisterNavigation());

        // TU & Kepsek BISA
        $tu = User::create([
            'name' => 'Staf Tata Usaha',
            'username' => 'staf_tu',
            'email' => 'tu@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $tu->assignRole('tata_usaha');

        $this->actingAs($tu);
        $this->assertTrue(TeacherProfileResource::canAccess());
        $this->assertTrue(TeacherProfileResource::shouldRegisterNavigation());
    }

    /**
     * Test: Guru Al-Qur'an TIDAK PERNAH melihat menu Wali Kelas & Prestasi Siswa, bahkan jika memiliki role campuran
     */
    public function test_quran_teachers_never_see_wali_kelas_or_prestasi_siswa(): void
    {
        $this->actingAs($this->guruQuran);

        // Prestasi siswa & Jadwal piket (jika tidak bertugas)
        $this->assertFalse(StudentAchievementResource::canAccess());
        $this->assertFalse(StudentAchievementResource::shouldRegisterNavigation());
        $this->assertFalse(DutyScheduleResource::canAccess());
        $this->assertFalse(DutyScheduleResource::shouldRegisterNavigation());

        // 8 Fitur Wali Kelas
        $this->assertFalse(RekapNilaiKelas::canAccess());
        $this->assertFalse(RekapNilaiKelas::shouldRegisterNavigation());
        // Data Siswa (StudentResource) sesuai request user #8 dapat diakses seluruh role lembaga (termasuk guru al-qur'an),
        // namun pada guru al-qur'an dikelompokkan ke 'Fitur Guru Al-Qur\'an', BUKAN 'Fitur Wali Kelas'
        $this->assertTrue(StudentResource::canAccess());
        $this->assertEquals('Fitur Guru Al-Qur\'an', StudentResource::getNavigationGroup());
        $this->assertFalse(StudentCaseResource::canAccess());
        $this->assertFalse(StudentCaseResource::shouldRegisterNavigation());
        $this->assertFalse(CharacterScoreReport::canAccess());
        $this->assertFalse(CharacterScoreReport::shouldRegisterNavigation());
        $this->assertFalse(PresensiIzinSiswaResource::canAccess());
        $this->assertFalse(PresensiIzinSiswaResource::shouldRegisterNavigation());
        $this->assertFalse(ClassProgramResource::canAccess());
        $this->assertFalse(ClassProgramResource::shouldRegisterNavigation());
        $this->assertFalse(ParentSummonResource::canAccess());
        $this->assertFalse(ParentSummonResource::shouldRegisterNavigation());
        $this->assertFalse(ProgramEvaluationResource::canAccess());
        $this->assertFalse(ProgramEvaluationResource::shouldRegisterNavigation());

        // Combo Role: jika akun guru di DB memiliki role guru_alquran + guru_mapel + wali_kelas sekaligus
        $this->guruQuran->assignRole(['guru_mapel', 'wali_kelas']);
        $this->guruQuran->refresh();

        // Tetap terisolasi secara mutlak dari menu kurikulum mapel umum & wali kelas
        $this->assertFalse(InputNilaiMapel::canAccess());
        $this->assertFalse(InputNilaiMapel::shouldRegisterNavigation());
        $this->assertFalse(GradeResource::canAccess());
        $this->assertFalse(GradeResource::shouldRegisterNavigation());
        $this->assertFalse(RppmResource::canAccess());
        $this->assertFalse(RppmResource::shouldRegisterNavigation());
        $this->assertFalse(RekapNilaiKelas::canAccess());
        $this->assertFalse(RekapNilaiKelas::shouldRegisterNavigation());
        // Data Siswa (StudentResource) sesuai request user #8 dapat diakses seluruh role lembaga (termasuk guru al-qur'an),
        // namun pada guru al-qur'an dikelompokkan ke 'Fitur Guru Al-Qur\'an', BUKAN 'Fitur Wali Kelas'
        $this->assertTrue(StudentResource::canAccess());
        $this->assertEquals('Fitur Guru Al-Qur\'an', StudentResource::getNavigationGroup());
    }
}
