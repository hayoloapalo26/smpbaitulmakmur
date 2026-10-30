<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\ClassroomResource;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuranTeacherVsWaliKelasSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $waliKelas;
    protected User $guruTahsin;
    protected User $guruYanbua;
    protected Classroom $kelas7;
    protected Classroom $kelasTahsin;
    protected Classroom $kelasYanbua;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_yanbua', 'guard_name' => 'web']);

        Institution::create([
            'slug' => 'yayasan',
            'name' => 'Yayasan Baitul Makmur',
        ]);

        $this->institution = Institution::create([
            'slug' => 'smp',
            'name' => 'SMP Islam Baitul Makmur',
        ]);

        // 1. Wali Kelas Resmi (Akademik Kelas 7)
        $this->waliKelas = User::create([
            'name' => 'Septiana Makrifatul Rosida, S.Pd',
            'username' => 'septiana',
            'email' => 'septiana@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->waliKelas->assignRole('guru_mapel');

        // 2. Guru Al-Qur'an Tahsin
        $this->guruTahsin = User::create([
            'name' => 'Elly Anasrul Firdaus, M.Pd',
            'username' => 'elly_anasrul',
            'email' => 'elly@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruTahsin->assignRole('guru_quran_tahsin');

        // 3. Guru Al-Qur'an Yanbu'a
        $this->guruYanbua = User::create([
            'name' => 'Iqomatu Nouvi Khuluq, M.Pd',
            'username' => 'iqomatu',
            'email' => 'iqomatu@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruYanbua->assignRole('guru_quran_yanbua');

        // Buat kelas reguler
        $this->kelas7 = Classroom::create([
            'name' => 'Kelas 7',
            'tingkat' => 'SMP',
            'tipe' => Classroom::TIPE_REGULER,
            'wali_kelas_id' => $this->waliKelas->id,
            'institution_id' => $this->institution->id,
        ]);

        // Buat kelompok Al-Qur'an
        $this->kelasTahsin = Classroom::create([
            'name' => 'Tahsin',
            'tingkat' => 'Tahsin SMP',
            'tipe' => Classroom::TIPE_QURAN,
            'wali_kelas_id' => $this->guruTahsin->id,
            'institution_id' => $this->institution->id,
        ]);

        $this->kelasYanbua = Classroom::create([
            'name' => 'Yanbu\'a',
            'tingkat' => 'Yanbu\'a SMP',
            'tipe' => Classroom::TIPE_QURAN,
            'wali_kelas_id' => $this->guruYanbua->id,
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 1: Helper model membedakan secara tegas Kelas Akademik vs Kelompok Al-Qur'an
     */
    public function test_classroom_model_distinguishes_regular_and_quran_groups(): void
    {
        $this->assertTrue($this->kelas7->isReguler());
        $this->assertFalse($this->kelas7->isQuran());
        $this->assertEquals('Wali Kelas', $this->kelas7->penanggung_jawab_role_title);

        $this->assertTrue($this->kelasTahsin->isQuran());
        $this->assertFalse($this->kelasTahsin->isReguler());
        $this->assertEquals('Guru Pengampu Al-Qur\'an', $this->kelasTahsin->penanggung_jawab_role_title);

        $this->assertTrue($this->kelasYanbua->isQuran());
        $this->assertFalse($this->kelasYanbua->isReguler());
        $this->assertEquals('Guru Pengampu Al-Qur\'an', $this->kelasYanbua->penanggung_jawab_role_title);
    }

    /**
     * Test 2: Penetapan Guru Al-Qur'an TIDAK memberikan role wali_kelas dan mencabutnya jika terlanjur ada
     */
    public function test_quran_group_teacher_is_not_assigned_wali_kelas_role(): void
    {
        // Jalankan sinkronisasi role untuk kelas reguler: guru mapel dapat role wali_kelas
        ClassroomResource::syncTeacherRoles($this->kelas7);
        $this->waliKelas->refresh();
        $this->assertTrue($this->waliKelas->hasRole('wali_kelas'));

        // Simulasikan jika guru Tahsin sempat salah diberi role wali_kelas
        $this->guruTahsin->assignRole('wali_kelas');
        $this->assertTrue($this->guruTahsin->hasRole('wali_kelas'));

        // Jalankan sinkronisasi untuk kelas Tahsin: role wali_kelas HARUS dicabut dari guru Al-Qur'an!
        ClassroomResource::syncTeacherRoles($this->kelasTahsin);
        $this->guruTahsin->refresh();
        $this->assertFalse($this->guruTahsin->hasRole('wali_kelas'));
        $this->assertTrue($this->guruTahsin->isQuranTeacher());

        // Untuk guru Yanbu'a: tidak boleh pernah mendapatkan role wali_kelas
        ClassroomResource::syncTeacherRoles($this->kelasYanbua);
        $this->guruYanbua->refresh();
        $this->assertFalse($this->guruYanbua->hasRole('wali_kelas'));
        $this->assertTrue($this->guruYanbua->isQuranTeacher());
    }

    /**
     * Test 3: Scope reguler hanya mengambil kelas akademik dan mengabaikan kelompok Al-Qur'an
     */
    public function test_scope_reguler_excludes_quran_classes(): void
    {
        $regulerIds = Classroom::reguler()->pluck('id')->toArray();
        $this->assertContains($this->kelas7->id, $regulerIds);
        $this->assertNotContains($this->kelasTahsin->id, $regulerIds);
        $this->assertNotContains($this->kelasYanbua->id, $regulerIds);

        $quranIds = Classroom::quran()->pluck('id')->toArray();
        $this->assertNotContains($this->kelas7->id, $quranIds);
        $this->assertContains($this->kelasTahsin->id, $quranIds);
        $this->assertContains($this->kelasYanbua->id, $quranIds);
    }

    /**
     * Test 4: Relasi User classroomsAsWali hanya mengaitkan kelas reguler akademik
     */
    public function test_user_classrooms_as_wali_relation_only_includes_regular_classrooms(): void
    {
        $waliClasses = $this->waliKelas->classroomsAsWali()->pluck('id')->toArray();
        $this->assertContains($this->kelas7->id, $waliClasses);

        // Guru Al-Qur'an tidak memiliki kelas binaan sebagai Wali Kelas
        $tahsinWaliClasses = $this->guruTahsin->classroomsAsWali()->pluck('id')->toArray();
        $this->assertEmpty($tahsinWaliClasses);

        // Namun Guru Al-Qur'an memiliki kelompok pengampu Al-Qur'an
        $tahsinQuranClasses = $this->guruTahsin->quranClassesAsPengampu()->pluck('id')->toArray();
        $this->assertContains($this->kelasTahsin->id, $tahsinQuranClasses);
    }
}
