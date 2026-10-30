<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\TahfidzRanking;
use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Filament\Staff\Resources\RppmResource;
use App\Filament\Staff\Resources\StudentQuranPlacementResource;
use App\Http\Middleware\RedirectPortalUsers;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PakRomliDualRoleYayasanAndTahfidzTest extends TestCase
{
    use RefreshDatabase;

    protected User $pakRomli;
    protected Institution $yayasan;
    protected Institution $smpi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->yayasan = Institution::create(['id' => 1, 'name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']);
        $this->smpi = Institution::create(['id' => 2, 'name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp']);

        Role::firstOrCreate(['name' => 'ketua_yayasan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahfidz_lanjutan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        $this->pakRomli = User::create([
            'id' => 3,
            'name' => 'M. Romli, M.Ag',
            'email' => 'romli@gmail.com',
            'username' => 'pakromli',
            'password' => bcrypt('password'),
            'institution_id' => 1,
            'is_active' => true,
        ]);

        $this->pakRomli->assignRole('ketua_yayasan');
        $this->pakRomli->assignRole('guru_quran_tahfidz_lanjutan');
    }

    public function test_pak_romli_has_dual_roles_and_is_recognized_as_quran_teacher_and_yayasan(): void
    {
        $this->assertTrue($this->pakRomli->hasRole('ketua_yayasan'));
        $this->assertTrue($this->pakRomli->hasRole('guru_quran_tahfidz_lanjutan'));
        $this->assertTrue($this->pakRomli->hasRole('guru_alquran'));
        $this->assertTrue($this->pakRomli->isQuranTeacher());
        $this->assertEquals('Tahfidz Lanjutan', $this->pakRomli->getQuranSpecializationLabel());
        $this->assertEquals(1, $this->pakRomli->institution_id);
    }

    public function test_redirect_portal_users_middleware_allows_pak_romli_to_access_both_yayasan_and_staff(): void
    {
        $middleware = new RedirectPortalUsers();

        // 1. Akses panel yayasan
        $requestYayasan = Request::create('/yayasan', 'GET');
        $this->actingAs($this->pakRomli);
        $responseYayasan = $middleware->handle($requestYayasan, fn ($req) => response('OK_YAYASAN'));
        $this->assertEquals('OK_YAYASAN', $responseYayasan->getContent());

        // 2. Akses panel staff (untuk mengajar tahfidz)
        $requestStaff = Request::create('/staff', 'GET');
        $responseStaff = $middleware->handle($requestStaff, fn ($req) => response('OK_STAFF'));
        $this->assertEquals('OK_STAFF', $responseStaff->getContent());

        // 3. Akses halaman Al-Qur'an di staff
        $requestQuran = Request::create('/staff/quran-achievements', 'GET');
        $responseQuran = $middleware->handle($requestQuran, fn ($req) => response('OK_QURAN'));
        $this->assertEquals('OK_QURAN', $responseQuran->getContent());
    }

    public function test_pak_romli_can_access_quran_features_and_is_isolated_from_non_quran_features(): void
    {
        $this->actingAs($this->pakRomli);

        // Akses fitur Al-Qur'an aktif
        $this->assertTrue(QuranAchievementResource::canAccess());
        $this->assertTrue(QuranAchievementResource::shouldRegisterNavigation());
        $this->assertEquals('Fitur Guru Al-Qur\'an', QuranAchievementResource::getNavigationGroup());

        $this->assertTrue(TahfidzRanking::canAccess());
        $this->assertTrue(TahfidzRanking::shouldRegisterNavigation());

        // Fitur mapel akademik biasa (RPPM) tidak muncul di sidebar Guru Al-Qur'an
        $this->assertFalse(RppmResource::shouldRegisterNavigation());
    }

    public function test_student_quran_placement_includes_pak_romli_as_cross_institution_quran_teacher(): void
    {
        // Penempatan di SMPI (institution_id = 2) harus menyertakan Pak Romli dari Yayasan (institution_id = 1)
        $options = StudentQuranPlacementResource::getGuruQuranOptions(2);

        $this->assertArrayHasKey($this->pakRomli->id, $options);
        $this->assertStringContainsString('M. Romli, M.Ag', $options[$this->pakRomli->id]);
        $this->assertStringContainsString('Tahfidz Lanjutan', $options[$this->pakRomli->id]);
        $this->assertStringContainsString('Yayasan / Lintas Lembaga', $options[$this->pakRomli->id]);
    }

    public function test_pak_romli_can_be_assigned_multiple_institutions(): void
    {
        // Ustadz Romli dapat memiliki multiple units (Yayasan = 1 dan SMP = 2)
        $this->pakRomli->syncInstitutions([$this->yayasan->id, $this->smpi->id]);

        $this->assertTrue($this->pakRomli->belongsToInstitution(1));
        $this->assertTrue($this->pakRomli->belongsToInstitution(2));
        $this->assertContains(1, $this->pakRomli->institution_ids);
        $this->assertContains(2, $this->pakRomli->institution_ids);
        $this->assertCount(2, $this->pakRomli->institutions);
    }

    public function test_quran_score_input_by_pak_romli_for_smp_student_belongs_to_smp_not_yayasan(): void
    {
        // Setup santri di SMP (institution_id = 2)
        $studentSmp = \App\Models\Student::create([
            'name' => 'Santri SMP Binaan',
            'nis' => '12345',
            'jenis_kelamin' => 'L',
            'institution_id' => 2,
            'kelas_quran' => 'tahfidz_lanjutan',
            'guru_quran_id' => $this->pakRomli->id,
        ]);

        $this->actingAs($this->pakRomli);

        // Pak Romli (user institusi primer 1 / Yayasan) menginput nilai capaian Al-Qur'an santri SMP
        $achievement = \App\Models\QuranAchievement::create([
            'student_id' => $studentSmp->id,
            'teacher_id' => $this->pakRomli->id,
            'tanggal' => now()->toDateString(),
            'program' => 'tahfidz_lanjutan',
            'juz' => 1,
            'surah' => '1',
            'ayah' => '1-10',
            'scale_score' => 95,
            'predikat' => 'Mumtaz',
            'catatan' => 'Hafalan lancar mutqin',
        ]);

        // Verifikasi hasil inputan masuk ke rumahnya (SMP ID = 2), BUKAN ke Yayasan (ID = 1)
        $this->assertEquals(2, $achievement->institution_id, 'Capaian Al-Qur\'an santri SMP harus masuk ke unit SMP (institution_id = 2), bukan Yayasan.');

        // Verifikasi reading_quality_scores yang tersinkronisasi juga masuk ke unit SMP (institution_id = 2)
        $readingScore = \App\Models\ReadingQualityScore::where('student_id', $studentSmp->id)->first();
        $this->assertNotNull($readingScore);
        $this->assertEquals(2, $readingScore->institution_id, 'Reading quality score santri SMP harus masuk ke unit SMP (institution_id = 2).');
    }

    public function test_teaching_journal_input_by_pak_romli_for_smp_class_belongs_to_smp_not_yayasan(): void
    {
        $classroomSmp = \App\Models\Classroom::create([
            'name' => 'Kelas 8A SMP',
            'tingkat' => '8',
            'institution_id' => 2,
        ]);

        $subjectTahfidz = \App\Models\Subject::create([
            'name' => 'Tahfidz Al-Qur\'an',
            'kode' => 'TFZ-SMP',
            'institution_id' => 2,
        ]);

        $scheduleSmp = \App\Models\Schedule::create([
            'classroom_id' => $classroomSmp->id,
            'subject_id' => $subjectTahfidz->id,
            'teacher_id' => $this->pakRomli->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'institution_id' => 2,
        ]);

        $this->actingAs($this->pakRomli);

        // Pak Romli menginput jurnal mengajar di kelas SMP
        $journal = \App\Models\TeachingJournal::create([
            'schedule_id' => $scheduleSmp->id,
            'classroom_id' => $classroomSmp->id,
            'subject_id' => $subjectTahfidz->id,
            'teacher_id' => $this->pakRomli->id,
            'tanggal' => now()->toDateString(),
            'jam_ke' => 1,
            'materi' => 'Ziyadah Juz 2',
        ]);

        // Verifikasi jurnal mengajar masuk ke unit SMP (ID = 2), bukan Yayasan (ID = 1)
        $this->assertEquals(2, $journal->institution_id, 'Jurnal mengajar kelas SMP harus masuk ke unit SMP (institution_id = 2).');
    }
}
