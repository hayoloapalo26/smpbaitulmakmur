<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Filament\Staff\Resources\ReadingQualityScoreResource;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedQuranAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $guruQuran;
    protected Student $santri;
    protected Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => User::ROLE_GURU_QURAN_TAHSIN, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => User::ROLE_GURU_QURAN_TAHFIDZ_DASAR, 'guard_name' => 'web']);

        Institution::firstOrCreate(['id' => 1], ['slug' => 'yayasan', 'name' => 'Yayasan Baitul Makmur']);
        Institution::firstOrCreate(['id' => 2], ['slug' => 'smp', 'name' => 'SMP Islam Baitul Makmur']);

        $this->class = Classroom::create([
            'name' => '7A',
            'tingkat' => '7',
            'institution_id' => 2,
        ]);

        $this->guruQuran = User::create([
            'name' => 'Ustadz Ahmad Al-Hafidz',
            'username' => 'ust_ahmad',
            'email' => 'ahmad@quran.test',
            'password' => bcrypt('password123'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->guruQuran->assignRole(User::ROLE_GURU_QURAN_TAHSIN);

        $this->santri = Student::create([
            'institution_id' => 2,
            'name' => 'Santri Al-Fatih',
            'nis' => '0321',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-10',
            'classroom_id' => $this->class->id,
            'guru_quran_id' => $this->guruQuran->id,
            'kelas_quran' => 'tahsin',
        ]);
    }

    /**
     * Test: Menu Penilaian Guru Al-Qur'an disatukan menjadi 1 fitur saja di sidebar
     */
    public function test_quran_teacher_sidebar_has_only_one_assessment_feature(): void
    {
        $this->actingAs($this->guruQuran);

        // Fitur Utama: Penilaian & Capaian Al-Qur'an AKTIF di sidebar
        $this->assertTrue(QuranAchievementResource::shouldRegisterNavigation());
        $this->assertEquals('Fitur Guru Al-Qur\'an', QuranAchievementResource::getNavigationGroup());
        $this->assertEquals('Penilaian & Capaian Al-Qur\'an', QuranAchievementResource::getModelLabel());

        // Fitur Terpisah Lama: Dinonaktifkan dari navigasi Guru Al-Qur'an agar tidak dua kali kerja
        $this->assertFalse(ReadingQualityScoreResource::shouldRegisterNavigation());
    }

    /**
     * Test: Input satu pintu otomatis menyimpan setoran dan mensinkronisasi skor kualitas bacaan
     */
    public function test_single_door_assessment_saves_achievement_and_syncs_reading_quality(): void
    {
        $this->actingAs($this->guruQuran);

        $achievement = QuranAchievement::create([
            'institution_id'    => 2,
            'student_id'        => $this->santri->id,
            'teacher_id'        => $this->guruQuran->id,
            'program'           => 'tahsin',
            'type'              => ['ziyadah'],
            'juz'               => 30,
            'surah'             => '78',
            'ayah'              => '1-20',
            'scale_score'       => 92,
            'predikat'          => 'Mumtaz',
            'catatan'           => 'Makharijul huruf sangat fasih, hukum mad thobii konsisten.',
            'tanggal'           => '2026-10-30',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        $this->assertDatabaseHas('quran_achievements', [
            'id'          => $achievement->id,
            'student_id'  => $this->santri->id,
            'scale_score' => 92,
            'predikat'    => 'Mumtaz',
        ]);

        // Terverifikasi otomatis tersinkronisasi ke reading_quality_scores
        $this->assertDatabaseHas('reading_quality_scores', [
            'student_id'  => $this->santri->id,
            'teacher_id'  => $this->guruQuran->id,
            'scale_score' => 92,
            'predikat'    => 'Mumtaz',
            'program'     => 'tahsin',
        ]);
    }
}
