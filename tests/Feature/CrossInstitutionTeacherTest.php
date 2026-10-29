<?php

namespace Tests\Feature;

use App\Filament\Staff\Widgets\DailyTeachingScheduleWidget;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\TeacherSupervision;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrossInstitutionTeacherTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $smp;
    protected Institution $ma;
    protected User $guruMA;
    protected User $wakaSmp;
    protected User $kepsekSmp;
    protected Classroom $kelasSmp;
    protected Subject $mapelSmp;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->smp = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        $this->ma = Institution::firstOrCreate(
            ['id' => 3],
            ['name' => 'MA Baitul Makmur', 'slug' => 'ma-baitul-makmur']
        );

        // Guru ber-homebase di MA (Lembaga Induk: MA)
        $this->guruMA = User::create([
            'name' => 'Ustadz Zaid (Guru MA)',
            'email' => 'zaid@ma.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->ma->id,
            'is_active' => true,
        ]);
        $this->guruMA->assignRole('guru_mapel');

        // Waka Kurikulum SMP
        $this->wakaSmp = User::create([
            'name' => 'Waka Kurikulum SMP',
            'email' => 'waka_smp@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->smp->id,
            'is_active' => true,
        ]);
        $this->wakaSmp->assignRole('waka_kurikulum');

        // Kepala Sekolah SMP
        $this->kepsekSmp = User::create([
            'name' => 'Kepala Sekolah SMP',
            'email' => 'kepsek_smp@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->smp->id,
            'is_active' => true,
        ]);
        $this->kepsekSmp->assignRole('kepala_sekolah');

        // Kelas & Mapel di SMP
        $this->kelasSmp = Classroom::create([
            'name' => 'Kelas 7A',
            'tingkat' => '7',
            'institution_id' => $this->smp->id,
        ]);

        $this->mapelSmp = Subject::create([
            'kode' => 'ARB-SMP',
            'name' => 'Bahasa Arab SMP',
            'institution_id' => $this->smp->id,
        ]);

        try {
            \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));
        } catch (\Throwable $e) {
        }
    }

    public function test_cross_institution_teacher_can_be_assigned_and_view_schedules(): void
    {
        // 1. Waka Kurikulum SMP menugaskan Guru MA di kelas 7A SMP
        $routing = TeacherSubjectClass::create([
            'teacher_id' => $this->guruMA->id,
            'classroom_id' => $this->kelasSmp->id,
            'subject_id' => $this->mapelSmp->id,
        ]);
        $this->assertDatabaseHas('teacher_subject_classes', [
            'id' => $routing->id,
            'teacher_id' => $this->guruMA->id,
            'classroom_id' => $this->kelasSmp->id,
        ]);

        // Hari ini
        $hariIni = Schedule::getIndonesianDayName(Carbon::today()->toDateString());

        // 2. Waka Kurikulum SMP membuat jadwal mengajar di SMP untuk Guru MA
        $schedule = Schedule::create([
            'teacher_id' => $this->guruMA->id,
            'classroom_id' => $this->kelasSmp->id,
            'subject_id' => $this->mapelSmp->id,
            'hari' => $hariIni,
            'jam_ke' => 1,
        ]);
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'teacher_id' => $this->guruMA->id,
            'classroom_id' => $this->kelasSmp->id,
        ]);

        // 3. Saat Guru MA login: jadwal SMP miliknya BISA DILIHAT (tidak terhalang filter institution_id MA)
        $this->actingAs($this->guruMA);

        $mySchedules = Schedule::where('teacher_id', $this->guruMA->id)->get();
        $this->assertCount(1, $mySchedules);
        $this->assertEquals($schedule->id, $mySchedules->first()->id);
        $this->assertEquals($this->smp->id, $mySchedules->first()->classroom->institution_id);

        // 4. Widget jadwal mengajar harian di dashboard memuat jadwal SMP
        Livewire::test(DailyTeachingScheduleWidget::class)
            ->assertCanSeeTableRecords([$schedule]);
    }

    public function test_principal_can_supervise_cross_institution_teacher(): void
    {
        // Guru MA mengajar di SMP
        TeacherSubjectClass::create([
            'institution_id' => $this->smp->id,
            'teacher_id' => $this->guruMA->id,
            'classroom_id' => $this->kelasSmp->id,
            'subject_id' => $this->mapelSmp->id,
        ]);

        // Kepala Sekolah SMP menjadwalkan supervisi untuk Guru MA di SMP
        $supervisi = TeacherSupervision::create([
            'institution_id' => $this->smp->id,
            'teacher_id' => $this->guruMA->id,
            'supervisor_id' => $this->kepsekSmp->id,
            'supervisor_name' => $this->kepsekSmp->name,
            'jalur_supervisi' => 'kepala_sekolah',
            'status' => 'dijadwalkan',
            'jadwal' => now()->addDays(2),
            'mata_pelajaran' => 'Bahasa Arab SMP',
            'kelas_semester' => 'Kelas 7A / Semester 1',
            'catatan' => 'Supervisi guru lintas lembaga MA di kelas SMP',
        ]);

        // Guru MA menerima notifikasi jadwal supervisi dari Kepala Sekolah SMP
        $notif = $this->guruMA->notifications()->latest()->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('Kepala Sekolah', $notif->data['title']);

        // Saat Guru MA login: supervisi SMP ini muncul di dashboard miliknya
        $this->actingAs($this->guruMA);
        $dashboard = new \App\Filament\Staff\Pages\Dashboard();
        $schedules = $dashboard->upcomingSchedules;
        $this->assertCount(1, $schedules);
        $this->assertEquals($supervisi->id, $schedules->first()['id']);
        $this->assertEquals($this->kepsekSmp->name, $schedules->first()['assessor_name']);
    }

    public function test_grades_and_assessments_strictly_belong_to_student_institution_when_input_by_cross_institution_teacher(): void
    {
        $ta = \App\Models\AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $siswaSmp = \App\Models\Student::create([
            'name' => 'Ahmad Santoso (Siswa SMP)',
            'nis' => '1001',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-10',
            'institution_id' => $this->smp->id,
            'classroom_id' => $this->kelasSmp->id,
        ]);

        // Kepala Sekolah MA
        $kepsekMA = User::create([
            'name' => 'Kepala Sekolah MA',
            'email' => 'kepsek_ma@ma.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->ma->id,
            'is_active' => true,
        ]);
        $kepsekMA->assignRole('kepala_sekolah');

        // Guru MA menginput nilai untuk Siswa SMP
        $this->actingAs($this->guruMA);

        $grade = \App\Models\Grade::create([
            'student_id'       => $siswaSmp->id,
            'classroom_id'     => $this->kelasSmp->id,
            'subject_id'       => $this->mapelSmp->id,
            'teacher_id'       => $this->guruMA->id,
            'academic_year_id' => $ta->id,
            'semester'         => 'Ganjil',
            'type'             => 'pengetahuan',
            'score'            => 88,
        ]);

        // 1. Verifikasi di database: institution_id nilai OTOMATIS = SMP (2), BUKAN MA (3)
        $this->assertEquals($this->smp->id, $grade->institution_id);
        $this->assertDatabaseHas('grades', [
            'id'             => $grade->id,
            'student_id'     => $siswaSmp->id,
            'institution_id' => $this->smp->id,
            'teacher_id'     => $this->guruMA->id,
        ]);

        // 2. Ketika Kepala Sekolah SMP login: Nilai ini MUNCUL di database SMP
        $this->actingAs($this->kepsekSmp);
        $smpGrades = \App\Models\Grade::all();
        $this->assertTrue($smpGrades->contains('id', $grade->id));

        // 3. Ketika Kepala Sekolah MA login: Nilai siswa SMP TIDAK PERNAH MUNCUL di MA
        $this->actingAs($kepsekMA);
        $maGrades = \App\Models\Grade::all();
        $this->assertFalse($maGrades->contains('id', $grade->id));
        $this->assertCount(0, $maGrades);

        // 4. Ketika Guru MA login: Nilai tetap bisa dilihat guru bersangkutan untuk evaluasi mengajar
        $this->actingAs($this->guruMA);
        $guruGrades = \App\Models\Grade::where('teacher_id', $this->guruMA->id)->get();
        $this->assertTrue($guruGrades->contains('id', $grade->id));
    }
}
