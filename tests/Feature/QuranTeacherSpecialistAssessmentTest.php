<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Filament\Staff\Resources\QuranAchievementResource\Pages\ManageQuranAchievements;
use App\Filament\Staff\Resources\ReadingQualityScoreResource;
use App\Filament\Staff\Resources\ReadingQualityScoreResource\Pages\ManageReadingQualityScores;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuranTeacherSpecialistAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guruTahsin;
    protected User $guruYanbua;
    protected User $guruMapel;
    protected Classroom $classroom;
    protected Student $santri1;
    protected Student $santri2;
    protected Student $santri3;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_yanbua', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'koordinator_madin', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->classroom = Classroom::create([
            'name' => 'Kelas 7A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
        ]);

        // Guru Tahsin (pure Quran teacher)
        $this->guruTahsin = User::create([
            'name' => 'Ust. Chisbulloh (Tahsin)',
            'username' => 'chisbulloh_tahsin',
            'email' => 'tahsin@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruTahsin->assignRole('guru_quran_tahsin');

        // Guru Yanbu'a
        $this->guruYanbua = User::create([
            'name' => 'Ust. Zaenal (Yanbu\'a)',
            'username' => 'zaenal_yanbua',
            'email' => 'yanbua@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruYanbua->assignRole('guru_quran_yanbua');

        // Guru Mapel Umum
        $this->guruMapel = User::create([
            'name' => 'Guru Matematika',
            'username' => 'guru_mtk',
            'email' => 'mtk@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruMapel->assignRole('guru_mapel');

        // Santri di kelompok Tahsin
        $this->santri1 = Student::create([
            'name' => 'Santri Alif',
            'nis' => 'Q101',
            'classroom_id' => $this->classroom->id,
            'kelas_quran' => 'tahsin',
            'guru_quran_id' => $this->guruTahsin->id,
            'institution_id' => $this->institution->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-01-01',
        ]);

        $this->santri2 = Student::create([
            'name' => 'Santri Baa',
            'nis' => 'Q102',
            'classroom_id' => $this->classroom->id,
            'kelas_quran' => 'tahsin',
            'guru_quran_id' => $this->guruTahsin->id,
            'institution_id' => $this->institution->id,
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2012-02-02',
        ]);

        // Santri di kelompok Yanbua
        $this->santri3 = Student::create([
            'name' => 'Santri Jim',
            'nis' => 'Q103',
            'classroom_id' => $this->classroom->id,
            'kelas_quran' => 'yanbua',
            'guru_quran_id' => $this->guruYanbua->id,
            'institution_id' => $this->institution->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-03-03',
        ]);
    }

    /**
     * Test 1: Guru Al-Qur'an murni tidak melihat menu nilai mapel CP/TP umum di navigasi
     */
    public function test_pure_quran_teacher_does_not_see_general_mapel_grade_menu(): void
    {
        $this->actingAs($this->guruTahsin);
        $this->assertFalse(GradeResource::shouldRegisterNavigation());

        // Namun Guru Mapel umum tetap melihatnya
        $this->actingAs($this->guruMapel);
        $this->assertTrue(GradeResource::shouldRegisterNavigation());
    }

    /**
     * Test 2: Guru Al-Qur'an memiliki akses ke Capaian Quran & Skor Kualitas Bacaan
     */
    public function test_quran_teacher_has_access_to_quran_assessment_features(): void
    {
        $this->actingAs($this->guruTahsin);

        $this->assertTrue(QuranAchievementResource::canAccess());
        $this->assertTrue(ReadingQualityScoreResource::canAccess());
    }

    /**
     * Test 3: Input Skor Kualitas Bacaan & Tahsin sekelompok santri (Batch Format Jurnal Mengajar)
     */
    public function test_batch_reading_quality_score_input_for_quran_group(): void
    {
        $this->actingAs($this->guruTahsin);

        $payload = [
            'program' => 'tahsin',
            'tanggal' => '2026-10-30',
            'jilid_halaman' => 'Juz 30 Surah An-Naba Ayat 1-20',
            'student_scores' => [
                $this->santri1->id => [
                    'score' => '92',
                    'catatan' => 'Makharijul huruf sangat tepat dan tartil.',
                ],
                $this->santri2->id => [
                    'score' => '85',
                    'catatan' => 'Lancar dan tajwid konsisten.',
                ],
            ],
        ];

        Livewire::test(ManageReadingQualityScores::class)
            ->callAction('input_skor_rombel', $payload)
            ->assertHasNoActionErrors();

        $this->assertDatabaseCount('reading_quality_scores', 2);

        $this->assertDatabaseHas('reading_quality_scores', [
            'student_id' => $this->santri1->id,
            'teacher_id' => $this->guruTahsin->id,
            'program' => 'tahsin',
            'scale_score' => 92,
            'predikat' => 'Sangat Baik',
            'jilid_halaman' => 'Juz 30 Surah An-Naba Ayat 1-20',
            'catatan_guru' => 'Makharijul huruf sangat tepat dan tartil.',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        $this->assertDatabaseHas('reading_quality_scores', [
            'student_id' => $this->santri2->id,
            'teacher_id' => $this->guruTahsin->id,
            'program' => 'tahsin',
            'scale_score' => 85,
            'predikat' => 'Baik',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);
    }

    /**
     * Test 4: Input Setoran Hafalan sekelompok santri (Batch Format Jurnal Mengajar)
     */
    public function test_batch_quran_achievement_input_for_quran_group(): void
    {
        $this->actingAs($this->guruTahsin);

        $payload = [
            'program' => 'tahsin',
            'tanggal' => '2026-10-30',
            'type' => 'ziyadah',
            'default_juz' => 30,
            'default_surah' => '78',
            'default_ayah' => '1-10',
            'student_achievements' => [
                $this->santri1->id => [
                    'active' => true,
                    'type' => 'ziyadah',
                    'juz' => 30,
                    'surah' => '78',
                    'ayah' => '1-15',
                    'predikat' => 'Mumtaz',
                    'catatan' => 'Mutqin lancar sekali tanpa salah.',
                ],
                $this->santri2->id => [
                    'active' => true,
                    'type' => 'ziyadah',
                    'juz' => 30,
                    'surah' => '78',
                    'ayah' => '1-10',
                    'predikat' => 'Jayyid Jiddan',
                    'catatan' => 'Lancar baik, tajwid rapi.',
                ],
                // Santri 3 tidak aktif (tidak menyetor hari ini)
                $this->santri3->id => [
                    'active' => false,
                    'type' => 'ziyadah',
                    'juz' => 30,
                    'surah' => '78',
                    'ayah' => '1-10',
                    'predikat' => 'Jayyid Jiddan',
                    'catatan' => '',
                ],
            ],
        ];

        Livewire::test(ManageQuranAchievements::class)
            ->callAction('input_setoran_rombel', $payload)
            ->assertHasNoActionErrors();

        // Santri 3 tidak dicentang, sehingga hanya 2 record tersimpan
        $this->assertDatabaseCount('quran_achievements', 2);

        $this->assertDatabaseHas('quran_achievements', [
            'student_id' => $this->santri1->id,
            'teacher_id' => $this->guruTahsin->id,
            'program' => 'tahsin',
            'juz' => 30,
            'surah' => '78',
            'ayah' => '1-15',
            'predikat' => 'Mumtaz',
            'catatan' => 'Mutqin lancar sekali tanpa salah.',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        $this->assertDatabaseHas('quran_achievements', [
            'student_id' => $this->santri2->id,
            'teacher_id' => $this->guruTahsin->id,
            'program' => 'tahsin',
            'juz' => 30,
            'surah' => '78',
            'ayah' => '1-10',
            'predikat' => 'Jayyid Jiddan',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);
    }

    /**
     * Test 5: Guru Al-Qur'an tidak perlu memilih dropdown kelas (program otomatis terisi dari spesialisasi akun)
     */
    public function test_quran_teacher_auto_resolves_class_without_selecting_dropdown(): void
    {
        $this->actingAs($this->guruYanbua);

        // Tanpa field 'program' dalam payload (karena dropdown kelas dihilangkan untuk guru Al-Qur'an)
        $payload = [
            'tanggal' => '2026-10-30',
            'jilid_halaman' => 'Yanbu\'a Jilid 2 Hal 15',
            'student_scores' => [
                $this->santri3->id => [
                    'score' => '88',
                    'catatan' => 'Lancar membaca huruf hijaiyah berharakat.',
                ],
            ],
        ];

        Livewire::test(ManageReadingQualityScores::class)
            ->callAction('input_skor_rombel', $payload)
            ->assertHasNoActionErrors();

        // Otomatis tersimpan dengan program 'yanbua' sesuai peran guru Yanbu'a
        $this->assertDatabaseHas('reading_quality_scores', [
            'student_id' => $this->santri3->id,
            'teacher_id' => $this->guruYanbua->id,
            'program' => 'yanbua',
            'scale_score' => 88,
            'jilid_halaman' => 'Yanbu\'a Jilid 2 Hal 15',
        ]);
    }
}
