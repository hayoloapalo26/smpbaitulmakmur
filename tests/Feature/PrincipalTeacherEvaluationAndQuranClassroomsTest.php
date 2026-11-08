<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\ClassroomResource;
use App\Filament\Staff\Resources\PrincipalTeacherEvaluationResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\PrincipalTeacherEvaluation;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherCompetencyTest;
use App\Models\TeacherObservation;
use App\Models\TeacherSupervision;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Services\TeacherKpiService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrincipalTeacherEvaluationAndQuranClassroomsTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $superadmin;
    protected User $headmaster;
    protected User $teacherMapel;
    protected User $guruTfzDasar;
    protected User $guruTfzLanjutan;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        $roles = [
            'superadmin', 'kepala_sekolah', 'waka_kurikulum',
            'guru_mapel', 'wali_kelas', 'guru_alquran',
            'guru_quran_yanbua', 'guru_quran_tahsin',
            'guru_quran_tahfidz_dasar', 'guru_quran_tahfidz_lanjutan',
            'tim_pengembang_penjamin_mutu_yayasan',
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        Institution::create([
            'id' => 1,
            'name' => 'Yayasan Baitul Makmur',
            'slug' => 'yayasan',
        ]);

        $this->institution = Institution::create([
            'id' => 2,
            'name' => 'SMP Baitul Makmur',
            'slug' => 'smp-baitul-makmur',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
        ]);
        $this->superadmin->assignRole('superadmin');

        $this->headmaster = User::create([
            'name' => 'Kepala Sekolah SMP',
            'email' => 'kepsek@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
        ]);
        $this->headmaster->assignRole('kepala_sekolah');

        $this->teacherMapel = User::create([
            'name' => 'Ustadz Budi',
            'email' => 'budi@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
        ]);
        $this->teacherMapel->assignRole('guru_mapel');

        $this->guruTfzDasar = User::create([
            'name' => 'Ustadz Romli',
            'email' => 'romli@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
        ]);
        $this->guruTfzDasar->assignRole('guru_quran_tahfidz_dasar');

        $this->guruTfzLanjutan = User::create([
            'name' => 'Ustadz Lukman',
            'email' => 'lukman@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
        ]);
        $this->guruTfzLanjutan->assignRole('guru_quran_tahfidz_lanjutan');
    }

    /**
     * Test 1: Tahfidz Dasar and Tahfidz Lanjutan classrooms are correctly classified as Quran rombel.
     */
    public function test_tahfidz_dasar_and_lanjutan_classrooms_are_quran_rombel(): void
    {
        $dasarCls = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'Tahfidz Dasar',
            'tingkat' => 'Tahfidz Dasar SMP',
            'tipe' => Classroom::TIPE_QURAN,
            'wali_kelas_id' => $this->guruTfzDasar->id,
        ]);

        $lanjutanCls = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'Tahfidz Lanjutan',
            'tingkat' => 'Tahfidz Lanjutan SMP',
            'tipe' => Classroom::TIPE_QURAN,
            'wali_kelas_id' => $this->guruTfzLanjutan->id,
        ]);

        $this->assertTrue($dasarCls->isQuran());
        $this->assertFalse($dasarCls->isReguler());
        $this->assertEquals('Guru Pengampu Al-Qur\'an', $dasarCls->penanggung_jawab_role_title);

        $this->assertTrue($lanjutanCls->isQuran());
        $this->assertFalse($lanjutanCls->isReguler());
        $this->assertEquals('Guru Pengampu Al-Qur\'an', $lanjutanCls->penanggung_jawab_role_title);

        // Sync roles verification
        ClassroomResource::syncTeacherRoles($dasarCls);
        ClassroomResource::syncTeacherRoles($lanjutanCls);

        $this->guruTfzDasar->refresh();
        $this->guruTfzLanjutan->refresh();

        $this->assertFalse($this->guruTfzDasar->hasRole('wali_kelas'));
        $this->assertTrue($this->guruTfzDasar->hasRole('guru_quran_tahfidz_dasar'));
        $this->assertTrue($this->guruTfzDasar->isQuranTeacher());

        $this->assertFalse($this->guruTfzLanjutan->hasRole('wali_kelas'));
        $this->assertTrue($this->guruTfzLanjutan->hasRole('guru_quran_tahfidz_lanjutan'));
        $this->assertTrue($this->guruTfzLanjutan->isQuranTeacher());
    }

    /**
     * Test 2: Student count and real students for Tahfidz Dasar & Lanjutan.
     */
    public function test_student_count_for_tahfidz_dasar_and_lanjutan(): void
    {
        $regularCls = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'Kelas 7A',
            'tingkat' => '7',
            'tipe' => Classroom::TIPE_REGULER,
        ]);

        $dasarCls = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'Tahfidz Dasar',
            'tingkat' => 'Tahfidz Dasar SMP',
            'tipe' => Classroom::TIPE_QURAN,
            'wali_kelas_id' => $this->guruTfzDasar->id,
        ]);

        $lanjutanCls = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'Tahfidz Lanjutan',
            'tingkat' => 'Tahfidz Lanjutan SMP',
            'tipe' => Classroom::TIPE_QURAN,
            'wali_kelas_id' => $this->guruTfzLanjutan->id,
        ]);

        // Student 1 in Tahfidz Dasar
        $santri1 = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Santri Tahfidz 1',
            'nis' => '101',
            'classroom_id' => $regularCls->id,
            'kelas_quran' => 'tahfidz_dasar',
            'guru_quran_id' => $this->guruTfzDasar->id,
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
        ]);

        // Student 2 in Tahfidz Lanjutan
        $santri2 = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Santri Tahfidz 2',
            'nis' => '102',
            'classroom_id' => $regularCls->id,
            'kelas_quran' => 'tahfidz_lanjutan',
            'guru_quran_id' => $this->guruTfzLanjutan->id,
            'tanggal_lahir' => '2012-02-02',
            'jenis_kelamin' => 'P',
        ]);

        $this->assertEquals(1, $dasarCls->getRealStudentsCount());
        $this->assertEquals(1, $lanjutanCls->getRealStudentsCount());

        $this->assertTrue($dasarCls->getRealStudents()->contains('id', $santri1->id));
        $this->assertFalse($dasarCls->getRealStudents()->contains('id', $santri2->id));

        $this->assertTrue($lanjutanCls->getRealStudents()->contains('id', $santri2->id));
        $this->assertFalse($lanjutanCls->getRealStudents()->contains('id', $santri1->id));
    }

    /**
     * Test 3: Kepala Sekolah can create monthly PrincipalTeacherEvaluation.
     */
    public function test_kepala_sekolah_can_evaluate_teacher_monthly(): void
    {
        $this->actingAs($this->headmaster);

        $eval = PrincipalTeacherEvaluation::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacherMapel->id,
            'evaluator_id' => $this->headmaster->id,
            'academic_year_id' => $this->academicYear->id,
            'bulan' => 11,
            'tahun' => 2026,
            'tanggal_penilaian' => now()->toDateString(),
            'score' => 92.5,
            'aspek_penilaian' => [
                'kedisiplinan' => 95,
                'pembelajaran' => 90,
                'administrasi' => 95,
                'keteladanan' => 90,
            ],
            'catatan' => 'Ustadz Budi menunjukkan dedikasi dan profesionalitas tinggi.',
        ]);

        $this->assertDatabaseHas('principal_teacher_evaluations', [
            'id' => $eval->id,
            'teacher_id' => $this->teacherMapel->id,
            'evaluator_id' => $this->headmaster->id,
            'bulan' => 11,
            'tahun' => 2026,
            'score' => 92.5,
            'predikat' => 'Sangat Baik (A)',
        ]);

        $this->assertEquals('November 2026', $eval->periode_bulan_tahun);
        $this->assertEquals('success', PrincipalTeacherEvaluation::getPredicateBadgeColor(92.5));
    }

    /**
     * Test 4: 5 Progress KPI Guru calculation (30% Jurnal, 15% Supervisi, 15% PKG, 20% Observasi, 20% Penilaian KS).
     */
    public function test_five_progress_teacher_kpi_calculation(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 10, 8, 0, 0));

        // 1. Setup schedule & journal -> 100% attendance
        $cls = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => '7B',
            'tingkat' => '7',
        ]);
        $sub = Subject::create([
            'institution_id' => $this->institution->id,
            'name' => 'Bahasa Indonesia',
            'kode' => 'BINDO',
        ]);
        $sched = Schedule::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacherMapel->id,
            'classroom_id' => $cls->id,
            'subject_id' => $sub->id,
            'hari' => Schedule::getIndonesianDayName(now()),
            'jam_ke' => 1,
            'jam_mulai' => '07:30',
            'jam_selesai' => '08:30',
            'created_at' => now()->startOfDay(),
        ]);
        TeachingJournal::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacherMapel->id,
            'schedule_id' => $sched->id,
            'classroom_id' => $cls->id,
            'subject_id' => $sub->id,
            'jam_ke' => 1,
            'tanggal' => now()->toDateString(),
            'rencana_kegiatan_guru' => 'Teks Eksplanasi',
            'kegiatan_siswa' => 'Menulis Paragraf',
            'status' => 'selesai',
        ]);

        // 2. Supervisi (Score 80) -> Bobot 15%
        TeacherSupervision::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacherMapel->id,
            'supervisor_id' => $this->headmaster->id,
            'tanggal' => now()->toDateString(),
            'score' => 80.0,
            'status' => 'dinilai',
        ]);

        // 3. PKG (Score 90) -> Bobot 15%
        TeacherCompetencyTest::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacherMapel->id,
            'assessor_id' => $this->headmaster->id,
            'tanggal' => now()->toDateString(),
            'score' => 90.0,
        ]);

        // 4. Observasi Yayasan (Score 85) -> Bobot 20%
        TeacherObservation::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacherMapel->id,
            'observer_id' => $this->headmaster->id,
            'tanggal_observasi' => now()->toDateString(),
            'score' => 85.0,
        ]);

        // 5. Penilaian Kepala Sekolah (Score 95) -> Bobot 20%
        PrincipalTeacherEvaluation::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacherMapel->id,
            'evaluator_id' => $this->headmaster->id,
            'bulan' => 11,
            'tahun' => 2026,
            'tanggal_penilaian' => now()->toDateString(),
            'score' => 95.0,
        ]);

        // Calculation check:
        // Jurnal = 100 * 0.30 = 30.0
        // Supervisi = 80 * 0.15 = 12.0
        // PKG = 90 * 0.15 = 13.5
        // Observasi = 85 * 0.20 = 17.0
        // Penilaian KS = 95 * 0.20 = 19.0
        // Total = 30.0 + 12.0 + 13.5 + 17.0 + 19.0 = 91.5
        $kpiService = new TeacherKpiService();
        $result = $kpiService->calculateTeacherPerformanceEvaluation($this->teacherMapel->id, 'bulanan');

        $this->assertEquals(100.0, $result['jurnal_score']);
        $this->assertEquals(80.0, $result['supervisi_score']);
        $this->assertEquals(90.0, $result['pkg_score']);
        $this->assertEquals(85.0, $result['observasi_score']);
        $this->assertEquals(95.0, $result['kepsek_score']);
        $this->assertEquals(91.5, $result['final_score']);
        $this->assertEquals('Sangat Baik (A)', $result['predikat']);
    }

    /**
     * Test 5: Staff PrincipalTeacherEvaluationResource Livewire table renders cleanly.
     */
    public function test_staff_principal_teacher_evaluation_resource_renders_cleanly(): void
    {
        $this->actingAs($this->headmaster);

        PrincipalTeacherEvaluation::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacherMapel->id,
            'evaluator_id' => $this->headmaster->id,
            'bulan' => 11,
            'tahun' => 2026,
            'tanggal_penilaian' => now()->toDateString(),
            'score' => 88.0,
            'catatan' => 'Baik dan disiplin.',
        ]);

        Livewire::test(PrincipalTeacherEvaluationResource\Pages\ManagePrincipalTeacherEvaluations::class)
            ->assertSuccessful()
            ->assertSee($this->teacherMapel->name)
            ->assertSee('88.0%');
    }
}
