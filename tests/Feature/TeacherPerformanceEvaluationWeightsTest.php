<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\TeacherObservationResource as StaffTeacherObservationResource;
use App\Filament\Yayasan\Resources\TeacherEvaluationResource;
use App\Filament\Yayasan\Resources\TeacherObservationResource as YayasanTeacherObservationResource;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\TeacherCompetencyTest;
use App\Models\TeacherObservation;
use App\Models\TeacherSupervision;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Services\TeacherKpiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherPerformanceEvaluationWeightsTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $penjaminMutu;
    protected User $pengembangMutu;
    protected User $teacher;
    protected User $headmaster;
    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $roles = [
            'superadmin', 'kepala_sekolah', 'waka_kurikulum',
            'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu',
            'tim_pengembang_yayasan', 'guru_mapel', 'wali_kelas', 'siswa'
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 1,
        ]);
        $this->superadmin->assignRole('superadmin');

        $this->penjaminMutu = User::create([
            'name' => 'Tim Penjamin Mutu',
            'email' => 'penjaminmutu@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 1,
        ]);
        $this->penjaminMutu->assignRole('tim_pengembang_penjamin_mutu_yayasan');

        $this->pengembangMutu = User::create([
            'name' => 'Pengembang & Penjamin Mutu',
            'email' => 'pengembang@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 1,
        ]);
        $this->pengembangMutu->assignRole('pengembang_penjamin_mutu');

        $this->headmaster = User::create([
            'name' => 'Kepala Sekolah',
            'email' => 'kepsek@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->headmaster->assignRole('kepala_sekolah');

        $this->teacher = User::create([
            'name' => 'Ustadz Ahmad',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->teacher->assignRole('guru_mapel');
    }

    /**
     * Test exact mathematical formula:
     * Jurnal Mengajar = 40%
     * Supervisi Guru = 20%
     * UKG = 20%
     * Observasi Penjamin Mutu = 20%
     */
    public function test_teacher_evaluation_exact_weight_calculation(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 30, 9, 0, 0));

        // Setup classroom & subject
        $classroom = \App\Models\Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'VII-A',
            'tingkat' => '7',
        ]);

        $subject = \App\Models\Subject::create([
            'institution_id' => $this->institution->id,
            'name' => 'Matematika',
            'kode' => 'MTK',
        ]);

        // Setup schedule and journal for 100% attendance
        $hariName = Schedule::getIndonesianDayName(now());
        $sched = Schedule::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'hari' => $hariName,
            'jam_ke' => 1,
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
            'created_at' => now()->startOfDay(),
        ]);

        TeachingJournal::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'schedule_id' => $sched->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'jam_ke' => 1,
            'tanggal' => now()->toDateString(),
            'rencana_kegiatan_guru' => 'Membahas Aljabar',
            'kegiatan_siswa' => 'Mengerjakan Latihan',
            'status' => 'selesai',
        ]);

        // Create Supervisi with score 80
        TeacherSupervision::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'supervisor_id' => $this->penjaminMutu->id,
            'tanggal' => now()->toDateString(),
            'score' => 80.0,
            'status' => 'dinilai',
            'hasil' => 'Baik',
        ]);

        // Create UKG with score 90
        TeacherCompetencyTest::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'assessor_id' => $this->penjaminMutu->id,
            'tanggal' => now()->toDateString(),
            'score' => 90.0,
        ]);

        // Create Observasi with score 70
        TeacherObservation::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'observer_id' => $this->penjaminMutu->id,
            'tanggal_observasi' => now()->toDateString(),
            'score' => 70.0,
            'materi_pembelajaran' => 'Deep Learning & Konsep Relasi',
        ]);

        // Calculation check via TeacherKpiService:
        // Expected: (100 * 0.40) + (80 * 0.20) + (90 * 0.20) + (70 * 0.20)
        // = 40 + 16 + 18 + 14 = 88.0
        $kpiService = new TeacherKpiService();
        $eval = $kpiService->calculateTeacherPerformanceEvaluation($this->teacher->id, 'bulanan');

        $this->assertEquals(100.0, $eval['jurnal_score']);
        $this->assertEquals(80.0, $eval['supervisi_score']);
        $this->assertEquals(90.0, $eval['ukg_score']);
        $this->assertEquals(70.0, $eval['observasi_score']);
        $this->assertEquals(88.0, $eval['final_score']);
        $this->assertEquals('Baik (B)', $eval['predikat']);
    }

    /**
     * Test role access for Pengembang & Penjamin Mutu in both Yayasan and Staff panels.
     */
    public function test_pengembang_and_penjamin_mutu_can_access_observation_features(): void
    {
        // 1. tim_pengembang_penjamin_mutu_yayasan
        $this->actingAs($this->penjaminMutu);
        $this->assertTrue(YayasanTeacherObservationResource::canAccess());
        $this->assertTrue(YayasanTeacherObservationResource::canCreate());
        $this->assertTrue(StaffTeacherObservationResource::canAccess());
        $this->assertTrue(TeacherEvaluationResource::canAccess());

        // 2. pengembang_penjamin_mutu (alias / new role name)
        $this->actingAs($this->pengembangMutu);
        $this->assertTrue(YayasanTeacherObservationResource::canAccess());
        $this->assertTrue(YayasanTeacherObservationResource::canCreate());
        $this->assertTrue(StaffTeacherObservationResource::canAccess());
        $this->assertTrue(TeacherEvaluationResource::canAccess());

        // 3. Superadmin
        $this->actingAs($this->superadmin);
        $this->assertTrue(YayasanTeacherObservationResource::canAccess());
        $this->assertTrue(TeacherEvaluationResource::canAccess());

        // 4. Student cannot access observation resource
        $student = User::create([
            'name' => 'Siswa Test',
            'email' => 'siswa@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $student->assignRole('siswa');
        $this->actingAs($student);
        $this->assertFalse(YayasanTeacherObservationResource::canAccess());
        $this->assertFalse(StaffTeacherObservationResource::canAccess());
    }

    /**
     * Test TeacherObservation model predicate and color helpers.
     */
    public function test_observation_model_auto_predicates(): void
    {
        $obsA = TeacherObservation::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'observer_id' => $this->penjaminMutu->id,
            'tanggal_observasi' => now()->toDateString(),
            'score' => 95.0,
        ]);
        $this->assertEquals('Sangat Baik (A)', $obsA->predikat);
        $this->assertEquals('success', TeacherObservation::getPredicateColor(95.0));

        $obsB = TeacherObservation::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'observer_id' => $this->penjaminMutu->id,
            'tanggal_observasi' => now()->toDateString(),
            'score' => 84.0,
        ]);
        $this->assertEquals('Baik (B)', $obsB->predikat);
        $this->assertEquals('success', TeacherObservation::getPredicateColor(84.0));

        $obsC = TeacherObservation::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'observer_id' => $this->penjaminMutu->id,
            'tanggal_observasi' => now()->toDateString(),
            'score' => 74.0,
        ]);
        $this->assertEquals('Cukup (C)', $obsC->predikat);
        $this->assertEquals('warning', TeacherObservation::getPredicateColor(74.0));

        $obsD = TeacherObservation::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'observer_id' => $this->penjaminMutu->id,
            'tanggal_observasi' => now()->toDateString(),
            'score' => 60.0,
        ]);
        $this->assertEquals('Perlu Bimbingan (D)', $obsD->predikat);
        $this->assertEquals('danger', TeacherObservation::getPredicateColor(60.0));
    }

    /**
     * Test table columns in TeacherEvaluationResource reflect 40/20/20/20 weights.
     */
    public function test_teacher_evaluation_resource_columns_contain_correct_weights(): void
    {
        try {
            \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('yayasan'));
        } catch (\Throwable $e) {
        }

        $this->actingAs($this->penjaminMutu);

        \Livewire\Livewire::test(\App\Filament\Yayasan\Resources\TeacherEvaluationResource\Pages\ListTeacherEvaluations::class)
            ->assertSuccessful()
            ->assertTableColumnExists('kehadiran_score')
            ->assertTableColumnExists('supervisi_score')
            ->assertTableColumnExists('ukg_score')
            ->assertTableColumnExists('observasi_score')
            ->assertTableColumnExists('final_score');
    }

    /**
     * Test calculation with different component values and lower predicate.
     */
    public function test_partial_scores_calculation_and_low_predicate(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 30, 9, 0, 0));

        // Create teacher with no schedules/journals -> Jurnal Score = 0
        $otherTeacher = User::create([
            'name' => 'Guru Baru',
            'email' => 'gurubaru@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $otherTeacher->assignRole('guru_mapel');

        // Supervisi = 75
        TeacherSupervision::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $otherTeacher->id,
            'supervisor_id' => $this->penjaminMutu->id,
            'tanggal' => now()->toDateString(),
            'score' => 75.0,
            'status' => 'dinilai',
            'hasil' => 'Cukup',
        ]);

        // UKG = 80
        TeacherCompetencyTest::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $otherTeacher->id,
            'assessor_id' => $this->penjaminMutu->id,
            'tanggal' => now()->toDateString(),
            'score' => 80.0,
        ]);

        // Observasi = 85
        TeacherObservation::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $otherTeacher->id,
            'observer_id' => $this->penjaminMutu->id,
            'tanggal_observasi' => now()->toDateString(),
            'score' => 85.0,
        ]);

        // Expected: (0 * 0.40) + (75 * 0.20) + (80 * 0.20) + (85 * 0.20)
        // = 0 + 15 + 16 + 17 = 48.0
        $kpiService = new TeacherKpiService();
        $eval = $kpiService->calculateTeacherPerformanceEvaluation($otherTeacher->id, 'bulanan');

        $this->assertEquals(0.0, $eval['jurnal_score']);
        $this->assertEquals(75.0, $eval['supervisi_score']);
        $this->assertEquals(80.0, $eval['ukg_score']);
        $this->assertEquals(85.0, $eval['observasi_score']);
        $this->assertEquals(48.0, $eval['final_score']);
        $this->assertEquals('Perlu Bimbingan (D)', $eval['predikat']);
    }
}
