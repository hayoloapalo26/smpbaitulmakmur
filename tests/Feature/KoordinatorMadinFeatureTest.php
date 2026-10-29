<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\PenerimaanNilaiMadin;
use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Filament\Staff\Resources\ReadingQualityScoreResource;
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

class KoordinatorMadinFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $koordinatorMadin;
    protected User $guruAlquran1;
    protected User $guruAlquran2;
    protected Classroom $classroom;
    protected Student $student1;
    protected Student $student2;
    protected Subject $subjectQuran;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'koordinator_madin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->koordinatorMadin = User::create([
            'name' => 'Ustadz Elly Anasrul, M.Pd',
            'username' => 'elly_madin',
            'email' => 'elly.anasrul@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Koordinator Madin',
        ]);
        $this->koordinatorMadin->assignRole('koordinator_madin');

        $this->guruAlquran1 = User::create([
            'name' => 'Ustadzah Sri Wahyuni, S.Ag',
            'username' => 'sri_quran',
            'email' => 'sri@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru Al-Qur\'an',
        ]);
        $this->guruAlquran1->assignRole('guru_alquran');

        $this->guruAlquran2 = User::create([
            'name' => 'Ustadz Ahmad Al-Hafidz',
            'username' => 'ahmad_hafiz',
            'email' => 'ahmad@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru Al-Qur\'an',
        ]);
        $this->guruAlquran2->assignRole('guru_alquran');

        $this->classroom = Classroom::create([
            'name' => 'VII-Tahfidz',
            'tingkat' => '7',
            'wali_kelas_id' => $this->koordinatorMadin->id,
            'institution_id' => $this->institution->id,
        ]);

        $this->student1 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'name' => 'Muhammad Fatih',
            'nis' => '1001',
            'nisn' => '001001',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '2012-01-15',
        ]);

        $this->student2 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'name' => 'Aisyah Humaira',
            'nis' => '1002',
            'nisn' => '001002',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '2012-02-20',
        ]);

        $this->subjectQuran = Subject::create([
            'name' => 'Al-Qur’an Hadits & Tahfidz',
            'kode' => 'QURAN-01',
        ]);
    }

    public function test_koordinator_madin_can_access_penerimaan_nilai_page(): void
    {
        $this->actingAs($this->koordinatorMadin);

        $response = $this->get('/staff/penerimaan-nilai-madin');
        $response->assertSuccessful();

        $this->assertTrue(PenerimaanNilaiMadin::canAccess());
    }

    public function test_koordinator_madin_can_receive_quran_achievement_from_teachers(): void
    {
        $this->actingAs($this->koordinatorMadin);

        // Guru Alquran 1 menginput setoran hafalan
        $achievement = QuranAchievement::create([
            'student_id' => $this->student1->id,
            'teacher_id' => $this->guruAlquran1->id,
            'type' => ['ziyadah'],
            'juz' => 30,
            'surah' => '78', // An-Naba
            'ayah' => '1-20',
            'catatan' => 'Makhraj huruf fasih dan lancar',
            'tanggal' => now()->toDateString(),
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        $this->assertEquals('Menunggu Verifikasi', $achievement->status_verifikasi);

        // Koordinator Madin menerima nilai tersebut
        Livewire::test(PenerimaanNilaiMadin::class)
            ->call('terimaCapaian', $achievement->id);

        $achievement->refresh();
        $this->assertEquals('Diterima', $achievement->status_verifikasi);
        $this->assertEquals($this->koordinatorMadin->id, $achievement->verified_by);
        $this->assertNotNull($achievement->verified_at);
    }

    public function test_koordinator_madin_can_receive_reading_quality_score(): void
    {
        $this->actingAs($this->koordinatorMadin);

        // Guru Alquran 2 menginput skor kualitas bacaan
        $score = ReadingQualityScore::create([
            'student_id' => $this->student2->id,
            'teacher_id' => $this->guruAlquran2->id,
            'program' => 'tahsin',
            'scale_score' => 88,
            'tanggal' => now()->toDateString(),
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        Livewire::test(PenerimaanNilaiMadin::class)
            ->call('terimaKualitas', $score->id);

        $score->refresh();
        $this->assertEquals('Diterima', $score->status_verifikasi);
        $this->assertEquals($this->koordinatorMadin->id, $score->verified_by);
    }

    public function test_koordinator_madin_can_receive_grade_for_quran_subject(): void
    {
        $this->actingAs($this->koordinatorMadin);

        // Input nilai mata pelajaran Al-Qur'an
        $grade = Grade::create([
            'student_id' => $this->student1->id,
            'classroom_id' => $this->classroom->id,
            'teacher_id' => $this->guruAlquran1->id,
            'subject_id' => $this->subjectQuran->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'formatif',
            'score' => 92,
            'tanggal' => now()->toDateString(),
            'catatan_guru' => 'Sangat menguasai tajwid hukum nun mati',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        Livewire::test(PenerimaanNilaiMadin::class)
            ->call('terimaNilai', $grade->id);

        $grade->refresh();
        $this->assertEquals('Diterima', $grade->status_verifikasi);
        $this->assertEquals($this->koordinatorMadin->id, $grade->verified_by);
    }

    public function test_koordinator_madin_can_bulk_accept_all_inputs_from_specific_teacher(): void
    {
        $this->actingAs($this->koordinatorMadin);

        // Guru 1 menginput beberapa data
        $c1 = QuranAchievement::create([
            'student_id' => $this->student1->id,
            'teacher_id' => $this->guruAlquran1->id,
            'type' => ['ziyadah'],
            'juz' => 30,
            'surah' => '79',
            'ayah' => '1-15',
            'tanggal' => now()->toDateString(),
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        $k1 = ReadingQualityScore::create([
            'student_id' => $this->student1->id,
            'teacher_id' => $this->guruAlquran1->id,
            'program' => 'tahfidz',
            'scale_score' => 95,
            'tanggal' => now()->toDateString(),
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        // Guru 2 menginput data lain (tidak boleh ikut terverifikasi jika aksi hanya untuk Guru 1)
        $c2 = QuranAchievement::create([
            'student_id' => $this->student2->id,
            'teacher_id' => $this->guruAlquran2->id,
            'type' => ['murajaah'],
            'juz' => 29,
            'surah' => '67',
            'ayah' => '1-30',
            'tanggal' => now()->toDateString(),
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        Livewire::test(PenerimaanNilaiMadin::class)
            ->call('terimaSemuaDariGuru', $this->guruAlquran1->id);

        $c1->refresh();
        $k1->refresh();
        $c2->refresh();

        $this->assertEquals('Diterima', $c1->status_verifikasi);
        $this->assertEquals('Diterima', $k1->status_verifikasi);
        $this->assertEquals('Menunggu Verifikasi', $c2->status_verifikasi, 'Data Guru 2 tidak boleh terpengaruh');
    }

    public function test_koordinator_madin_can_send_revision_note(): void
    {
        $this->actingAs($this->koordinatorMadin);

        $achievement = QuranAchievement::create([
            'student_id' => $this->student1->id,
            'teacher_id' => $this->guruAlquran1->id,
            'type' => ['ziyadah'],
            'juz' => 30,
            'surah' => '80',
            'ayah' => '1-10',
            'tanggal' => now()->toDateString(),
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        Livewire::test(PenerimaanNilaiMadin::class)
            ->call('openFeedbackModal', 'capaian', $achievement->id)
            ->set('catatanFeedback', 'Mohon setorkan ulang ayat 5-10 karena hukum idgham belum tepat.')
            ->call('simpanCatatanFeedback');

        $achievement->refresh();
        $this->assertEquals('Perlu Perbaikan', $achievement->status_verifikasi);
        $this->assertEquals('Mohon setorkan ulang ayat 5-10 karena hukum idgham belum tepat.', $achievement->catatan_koordinator);
    }
}
