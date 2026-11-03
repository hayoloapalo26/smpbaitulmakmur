<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\InputNilaiMapel;
use App\Filament\Staff\Pages\PenerimaanNilaiMadin;
use App\Filament\Staff\Pages\RekapNilaiKelas;
use App\Filament\Staff\Pages\TahfidzRanking;
use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Filament\Staff\Resources\ReadingQualityScoreResource;
use App\Filament\Staff\Resources\StudentQuranPlacementResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KoordinatorMadinRekapitulasiNilaiTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $yayasan;
    protected Institution $smp;
    protected AcademicYear $academicYear;
    protected User $koordinatorMadin;
    protected User $guruYanbua;
    protected User $guruTahsin;
    protected Classroom $kelas7A;
    protected Classroom $kelas7B;
    protected Student $santri1;
    protected Student $santri2;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'koordinator_madin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_yanbua', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);

        $this->yayasan = Institution::create([
            'id' => 1,
            'name' => 'Yayasan Baitul Makmur',
            'slug' => 'yayasan',
        ]);

        $this->smp = Institution::create([
            'id' => 2,
            'name' => 'SMP Baitul Makmur',
            'slug' => 'smp-baitul-makmur',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        // Koordinator Madin (sekaligus bisa memiliki peran guru Al-Qur'an)
        $this->koordinatorMadin = User::create([
            'name' => 'Ustadz Elly Anasrul (Koordinator Madin)',
            'email' => 'elly.anasrul@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->koordinatorMadin->assignRole(['koordinator_madin', 'guru_alquran']);

        // Guru Al-Qur'an 1: Yanbu'a
        $this->guruYanbua = User::create([
            'name' => 'Ustadz Zaenal Musthofa (Guru Yanbu\'a)',
            'email' => 'zainal.yanbua@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->guruYanbua->assignRole('guru_quran_yanbua');

        // Guru Al-Qur'an 2: Tahsin
        $this->guruTahsin = User::create([
            'name' => 'Ustadzah Iqomatu Nouvi (Guru Tahsin)',
            'email' => 'nouvi.tahsin@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->guruTahsin->assignRole('guru_quran_tahsin');

        // Kelas
        $this->kelas7A = Classroom::create([
            'name' => '7A',
            'tingkat' => '7',
            'institution_id' => 2,
            'tipe' => Classroom::TIPE_REGULER,
        ]);

        $this->kelas7B = Classroom::create([
            'name' => '7B',
            'tingkat' => '7',
            'institution_id' => 2,
            'tipe' => Classroom::TIPE_REGULER,
        ]);

        // Siswa 1 dibina Pak Zaenal (Yanbu'a)
        $this->santri1 = Student::create([
            'name' => 'Santri Binaan Zaenal',
            'nis' => '1001',
            'classroom_id' => $this->kelas7A->id,
            'guru_quran_id' => $this->guruYanbua->id,
            'kelas_quran' => 'yanbua',
            'institution_id' => 2,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-01-01',
        ]);

        // Siswa 2 dibina Bu Nouvi (Tahsin)
        $this->santri2 = Student::create([
            'name' => 'Santri Binaan Nouvi',
            'nis' => '1002',
            'classroom_id' => $this->kelas7B->id,
            'guru_quran_id' => $this->guruTahsin->id,
            'kelas_quran' => 'tahsin',
            'institution_id' => 2,
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2012-02-02',
        ]);

        // Input Nilai / Capaian oleh Guru Yanbu'a
        QuranAchievement::create([
            'student_id' => $this->santri1->id,
            'teacher_id' => $this->guruYanbua->id,
            'tanggal' => '2026-11-01',
            'type' => 'ziyadah',
            'program' => 'yanbua',
            'juz' => 30,
            'surah' => '78',
            'ayah' => '1-10',
            'scale_score' => 85,
            'predikat' => 'Jayyid Jiddan',
            'catatan' => 'Makhraj huruf jelas, kelancaran bagus.',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        ReadingQualityScore::create([
            'student_id' => $this->santri1->id,
            'teacher_id' => $this->guruYanbua->id,
            'program' => 'yanbua',
            'scale_score' => 85,
            'tanggal' => '2026-11-01',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        // Input Nilai / Capaian oleh Guru Tahsin
        QuranAchievement::create([
            'student_id' => $this->santri2->id,
            'teacher_id' => $this->guruTahsin->id,
            'tanggal' => '2026-11-02',
            'type' => 'ziyadah',
            'program' => 'tahsin',
            'juz' => 30,
            'surah' => '79',
            'ayah' => '1-15',
            'scale_score' => 92,
            'predikat' => 'Mumtaz',
            'catatan' => 'Fashahah dan ahkamul mad sempurna.',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        ReadingQualityScore::create([
            'student_id' => $this->santri2->id,
            'teacher_id' => $this->guruTahsin->id,
            'program' => 'tahsin',
            'scale_score' => 92,
            'tanggal' => '2026-11-02',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);
    }

    public function test_rekapitulasi_nilai_quran_navigation_registration_for_koordinator_madin(): void
    {
        $this->actingAs($this->koordinatorMadin);

        // Rekapitulasi Nilai muncul untuk Koordinator Madin
        $this->assertTrue(RekapNilaiKelas::canAccess());
        $this->assertTrue(RekapNilaiKelas::shouldRegisterNavigation());
        $this->assertEquals('Madrasah Diniyah (Madin)', RekapNilaiKelas::getNavigationGroup());
        $this->assertEquals('Rekapitulasi Nilai Al-Qur\'an', RekapNilaiKelas::getNavigationLabel());
        $this->assertEquals(2, RekapNilaiKelas::getNavigationSort());
    }

    public function test_koordinator_madin_can_view_rekap_scores_from_all_quran_teachers(): void
    {
        $this->actingAs($this->koordinatorMadin);

        // Livewire test on RekapNilaiKelas page
        Livewire::test(RekapNilaiKelas::class)
            ->assertSet('activeTab', 'tahfidz') // Otomatis default ke tab tahfidz untuk Koordinator Madin
            ->assertSee('Santri Binaan Zaenal')
            ->assertSee('Ustadz Zaenal Musthofa')
            ->assertSee('Santri Binaan Nouvi')
            ->assertSee('Ustadzah Iqomatu Nouvi')
            ->assertSee('85 (Yanbua)')
            ->assertSee('92 (Tahsin)');
    }

    public function test_koordinator_madin_can_filter_rekap_by_specific_teacher(): void
    {
        $this->actingAs($this->koordinatorMadin);

        Livewire::test(RekapNilaiKelas::class)
            ->set('activeTab', 'tahfidz')
            ->set('selectedTeacherId', $this->guruYanbua->id)
            ->assertSee('Santri Binaan Zaenal')
            ->assertDontSee('Santri Binaan Nouvi')
            ->set('selectedTeacherId', $this->guruTahsin->id)
            ->assertSee('Santri Binaan Nouvi')
            ->assertDontSee('Santri Binaan Zaenal');
    }

    public function test_penerimaan_nilai_madin_monitors_all_teachers_and_links_to_rekap(): void
    {
        $this->actingAs($this->koordinatorMadin);

        // Livewire test on PenerimaanNilaiMadin page
        Livewire::test(PenerimaanNilaiMadin::class)
            ->set('activeTab', 'monitoring_guru')
            ->assertSee('Ustadz Zaenal Musthofa')
            ->assertSee('Ustadzah Iqomatu Nouvi')
            ->assertSee('Rekap Nilai');
    }

    public function test_koordinator_madin_can_access_grade_resource_and_input_nilai_mapel(): void
    {
        $this->actingAs($this->koordinatorMadin);

        $this->assertTrue(GradeResource::canAccess());
        $this->assertTrue(GradeResource::shouldRegisterNavigation());
        $this->assertTrue(InputNilaiMapel::canAccess());
        $this->assertTrue(InputNilaiMapel::shouldRegisterNavigation());
        $this->assertTrue(StudentQuranPlacementResource::canAccess());
        $this->assertEquals('Madrasah Diniyah (Madin)', StudentQuranPlacementResource::getNavigationGroup());
    }
}
