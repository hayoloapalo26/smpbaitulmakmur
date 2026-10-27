<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\DutySchedule;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\User;
use App\Policies\DutySchedulePolicy;
use App\Services\DutyScheduleGeneratorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DutyScheduleSemesterGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $wakaKesiswaan;
    protected User $guru1;
    protected User $guru2;
    protected User $guru3;
    protected User $guru4;
    protected User $guru5;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(['slug' => 'smp-islam-baitul-makmur'], ['name' => 'SMP Islam Baitul Makmur']);

        $this->wakaKesiswaan = User::create([
            'name' => 'Waka Kesiswaan',
            'email' => 'wakakes@smp.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->wakaKesiswaan->assignRole('waka_kesiswaan');

        $this->guru1 = User::create([
            'name' => 'Guru Satu',
            'email' => 'guru1@smp.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru1->assignRole('guru_mapel');

        $this->guru2 = User::create([
            'name' => 'Guru Dua',
            'email' => 'guru2@smp.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru2->assignRole('guru_mapel');

        $this->guru3 = User::create([
            'name' => 'Guru Tiga',
            'email' => 'guru3@smp.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru3->assignRole('guru_mapel');

        $this->guru4 = User::create([
            'name' => 'Guru Empat',
            'email' => 'guru4@smp.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru4->assignRole('guru_mapel');

        $this->guru5 = User::create([
            'name' => 'Guru Lima',
            'email' => 'guru5@smp.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru5->assignRole('guru_mapel');
    }

    public function test_waka_kesiswaan_has_full_policy_management_and_teacher_can_only_view()
    {
        $policy = new DutySchedulePolicy();

        $this->assertTrue($policy->viewAny($this->wakaKesiswaan));
        $this->assertTrue($policy->create($this->wakaKesiswaan));

        $schedule = DutySchedule::create([
            'teacher_id' => $this->guru1->id,
            'tanggal' => Carbon::today()->toDateString(),
        ]);

        $this->assertTrue($policy->update($this->wakaKesiswaan, $schedule));
        $this->assertTrue($policy->delete($this->wakaKesiswaan, $schedule));

        // Guru Mapel biasa hanya bisa melihat
        $this->assertTrue($policy->viewAny($this->guru1));
        $this->assertTrue($policy->view($this->guru1, $schedule));
        $this->assertFalse($policy->create($this->guru1));
        $this->assertFalse($policy->update($this->guru1, $schedule));
        $this->assertFalse($policy->delete($this->guru1, $schedule));
    }

    public function test_generate_semester_schedule_creates_weekly_fixed_routine_for_entire_semester()
    {
        $service = new DutyScheduleGeneratorService();

        $teacherIds = [
            $this->guru1->id,
            $this->guru2->id,
            $this->guru3->id,
            $this->guru4->id,
            $this->guru5->id,
        ];

        // 1 Semester Ganjil: 1 Juli s/d 31 Desember 2026
        $result = $service->generateSemesterSchedule([
            'institution_id' => $this->institution->id,
            'semester' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'days_of_week' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            'teachers_per_day' => 1,
            'teacher_ids' => $teacherIds,
            'mode' => 'weekly_fixed',
            'overwrite' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(100, $result['count']);
        $this->assertNotEmpty($result['formation']);

        // Verifikasi bahwa hari Senin memiliki guru yang konsisten sepanjang semester
        $mondaySchedules = DutySchedule::whereBetween('tanggal', ['2026-07-01', '2026-12-31'])
            ->get()
            ->filter(fn ($s) => Schedule::getIndonesianDayName($s->tanggal) === 'Senin');

        $this->assertNotEmpty($mondaySchedules);
        $mondayTeachers = $mondaySchedules->pluck('teacher_id')->unique()->values()->all();

        // Dalam mode teachers_per_day = 1 dan 5 guru untuk 5 hari, guru Senin harus unik dan tetap
        $this->assertCount(1, $mondayTeachers);
    }

    public function test_next_semester_is_reshuffled_for_all_teachers()
    {
        $service = new DutyScheduleGeneratorService();

        $teacherIds = [
            $this->guru1->id,
            $this->guru2->id,
            $this->guru3->id,
            $this->guru4->id,
            $this->guru5->id,
        ];

        // Semester 1: Ganjil (2026-07-01 s/d 2026-12-31)
        $sem1 = $service->generateSemesterSchedule([
            'institution_id' => $this->institution->id,
            'semester' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'days_of_week' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            'teachers_per_day' => 1,
            'teacher_ids' => $teacherIds,
            'mode' => 'weekly_fixed',
            'avoid_previous_semester_day' => true,
            'overwrite' => true,
        ]);

        // Semester 2: Genap (2027-01-01 s/d 2027-06-30) diacak lagi!
        $sem2 = $service->generateSemesterSchedule([
            'institution_id' => $this->institution->id,
            'semester' => 'Genap',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'days_of_week' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            'teachers_per_day' => 1,
            'teacher_ids' => $teacherIds,
            'mode' => 'weekly_fixed',
            'avoid_previous_semester_day' => true,
            'overwrite' => true,
        ]);

        $this->assertTrue($sem1['success']);
        $this->assertTrue($sem2['success']);

        // Verifikasi bahwa jadwal semester 1 dan semester 2 keduanya ada
        $countSem1 = DutySchedule::whereBetween('tanggal', ['2026-07-01', '2026-12-31'])->count();
        $countSem2 = DutySchedule::whereBetween('tanggal', ['2027-01-01', '2027-06-30'])->count();

        $this->assertGreaterThan(100, $countSem1);
        $this->assertGreaterThan(100, $countSem2);

        // Ambil guru yang piket hari Senin di Semester 1
        $monSem1Teacher = DutySchedule::whereBetween('tanggal', ['2026-07-01', '2026-12-31'])
            ->get()
            ->first(fn ($s) => Schedule::getIndonesianDayName($s->tanggal) === 'Senin')
            ?->teacher_id;

        // Ambil guru yang piket hari Senin di Semester 2
        $monSem2Teacher = DutySchedule::whereBetween('tanggal', ['2027-01-01', '2027-06-30'])
            ->get()
            ->first(fn ($s) => Schedule::getIndonesianDayName($s->tanggal) === 'Senin')
            ?->teacher_id;

        $this->assertNotNull($monSem1Teacher);
        $this->assertNotNull($monSem2Teacher);

        // Dengan avoid_previous_semester_day = true, guru Senin Semester 2 harus berbeda dari guru Senin Semester 1!
        $this->assertNotEquals($monSem1Teacher, $monSem2Teacher);
    }

    public function test_teacher_scheduled_today_is_detected_as_piket_today()
    {
        $today = Carbon::today()->toDateString();

        // Daftarkan Guru Satu piket hari ini
        DutySchedule::create([
            'teacher_id' => $this->guru1->id,
            'tanggal' => $today,
        ]);

        $this->assertTrue($this->guru1->isPiketToday());
        $this->assertFalse($this->guru2->isPiketToday());
    }

    public function test_piket_dashboard_and_related_pages_accessible_by_piket_teacher()
    {
        $today = Carbon::today()->toDateString();

        DutySchedule::create([
            'teacher_id' => $this->guru1->id,
            'tanggal' => $today,
        ]);

        $this->actingAs($this->guru1);

        $this->assertTrue(\App\Filament\Staff\Pages\PiketDashboard::canAccess());
        $this->assertTrue(\App\Filament\Staff\Pages\AbsensiManual::canAccess());
        $this->assertTrue(\App\Filament\Staff\Pages\ScannerPresensi::canAccess());
        $this->assertTrue(\App\Filament\Staff\Resources\DailyAttendanceResource::canAccess());
        $this->assertTrue(\App\Filament\Staff\Resources\AttributeViolationResource::canAccess());
        $this->assertTrue(\App\Filament\Staff\Resources\DutySwapRequestResource::canAccess());
    }

    public function test_teacher_not_on_duty_today_cannot_access_or_see_piket_features()
    {
        // Guru Dua TIDAK dijadwalkan piket hari ini
        $this->actingAs($this->guru2);

        $this->assertFalse($this->guru2->isPiketToday());

        // Navigasi & Akses harus bernilai FALSE untuk Guru Dua
        $this->assertFalse(\App\Filament\Staff\Pages\PiketDashboard::canAccess());
        $this->assertFalse(\App\Filament\Staff\Pages\PiketDashboard::shouldRegisterNavigation());

        $this->assertFalse(\App\Filament\Staff\Pages\AbsensiManual::canAccess());
        $this->assertFalse(\App\Filament\Staff\Pages\AbsensiManual::shouldRegisterNavigation());

        $this->assertFalse(\App\Filament\Staff\Pages\ScannerPresensi::canAccess());
        $this->assertFalse(\App\Filament\Staff\Pages\ScannerPresensi::shouldRegisterNavigation());

        $this->assertFalse(\App\Filament\Staff\Resources\DailyAttendanceResource::canAccess());
        $this->assertFalse(\App\Filament\Staff\Resources\DailyAttendanceResource::shouldRegisterNavigation());

        $this->assertFalse(\App\Filament\Staff\Resources\AttributeViolationResource::canAccess());
        $this->assertFalse(\App\Filament\Staff\Resources\AttributeViolationResource::shouldRegisterNavigation());

        $this->assertFalse(\App\Filament\Staff\Resources\PrayerAttendanceResource::canAccess());
        $this->assertFalse(\App\Filament\Staff\Resources\PrayerAttendanceResource::shouldRegisterNavigation());

        // Namun Master Jadwal tetap bisa dilihat agar guru tahu kapan hari tugasnya
        $this->assertTrue(\App\Filament\Staff\Resources\DutyScheduleResource::canAccess());
    }

    public function test_attribute_violation_observer_creates_character_score_log_safely()
    {
        $classroom = \App\Models\Classroom::create([
            'name' => 'VII-A',
            'tingkat' => 7,
            'institution_id' => $this->institution->id,
        ]);

        $student = \App\Models\Student::create([
            'name' => 'Ahmad Siswa',
            'nis' => '12345',
            'classroom_id' => $classroom->id,
            'tanggal_lahir' => '2010-01-01',
            'jenis_kelamin' => 'L',
            'institution_id' => $this->institution->id,
        ]);

        $violation = \App\Models\AttributeViolation::create([
            'student_id' => $student->id,
            'teacher_id' => $this->guru1->id,
            'tanggal' => Carbon::today()->toDateString(),
            'attribute_type' => ['Dasi', 'Topi'],
            'jenis_pelanggaran' => 'Upacara bendera',
        ]);

        $this->assertDatabaseHas('character_score_logs', [
            'student_id' => $student->id,
            'source' => 'guru_piket',
            'point_change' => -1,
        ]);
    }
}
