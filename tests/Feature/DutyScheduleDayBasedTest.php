<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\DutySchedule;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\User;
use App\Policies\AttributeViolationPolicy;
use App\Policies\DailyAttendancePolicy;
use App\Policies\PrayerAttendancePolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DutyScheduleDayBasedTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $wakaKesiswaan;
    protected User $guruSenin;
    protected User $guruSelasa;
    protected User $guruRabu;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            ['semester' => 'Ganjil', 'is_active' => true]
        );

        $this->wakaKesiswaan = User::create([
            'name' => 'Waka Kesiswaan',
            'email' => 'waka_kesiswaan@test.com',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->wakaKesiswaan->assignRole('waka_kesiswaan');

        $this->guruSenin = User::create([
            'name' => 'Ustadz Ahmad (Senin)',
            'email' => 'ahmad_senin@test.com',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruSenin->assignRole('guru_mapel');

        $this->guruSelasa = User::create([
            'name' => 'Ustadz Budi (Selasa)',
            'email' => 'budi_selasa@test.com',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruSelasa->assignRole('guru_mapel');

        $this->guruRabu = User::create([
            'name' => 'Ustadzah Citra (Rabu)',
            'email' => 'citra_rabu@test.com',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruRabu->assignRole('guru_mapel');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset mocked time
        parent::tearDown();
    }

    /**
     * Test Waka Kesiswaan menginput jadwal piket cukup harinya saja tanpa tanggal.
     */
    public function test_duty_schedule_can_be_created_with_day_only_and_null_date(): void
    {
        $schedule = DutySchedule::create([
            'teacher_id' => $this->guruSenin->id,
            'hari' => 'Senin',
            'tanggal' => null,
            'institution_id' => $this->institution->id,
            'notes' => 'Piket rutin setiap hari Senin',
        ]);

        $this->assertDatabaseHas('duty_schedules', [
            'id' => $schedule->id,
            'teacher_id' => $this->guruSenin->id,
            'hari' => 'Senin',
            'tanggal' => null,
        ]);

        $this->assertNull($schedule->tanggal);
        $this->assertEquals('Senin', $schedule->hari);
        $this->assertNotEmpty($schedule->semester);
        $this->assertEquals($this->academicYear->id, $schedule->academic_year_id);
    }

    /**
     * Test guru otomatis bertugas saat hari berjalan cocok dengan hari penugasan tanpa perlu tanggal.
     */
    public function test_teacher_automatically_becomes_piket_today_when_day_matches(): void
    {
        // Setup jadwal hari Senin untuk Guru Senin dan hari Selasa untuk Guru Selasa
        DutySchedule::create([
            'teacher_id' => $this->guruSenin->id,
            'hari' => 'Senin',
            'tanggal' => null,
            'institution_id' => $this->institution->id,
        ]);

        DutySchedule::create([
            'teacher_id' => $this->guruSelasa->id,
            'hari' => 'Selasa',
            'tanggal' => null,
            'institution_id' => $this->institution->id,
        ]);

        // Simulasikan hari ini adalah hari Senin (2 November 2026 adalah Senin)
        Carbon::setTestNow('2026-11-02 07:30:00');
        $this->assertEquals('Senin', Schedule::getIndonesianDayName(Carbon::today()));

        // Guru Senin otomatis bertugas hari ini
        $this->assertTrue($this->guruSenin->isPiketToday());
        $this->assertTrue($this->guruSenin->isPiketOnDate());

        // Guru Selasa TIDAK bertugas di hari Senin
        $this->assertFalse($this->guruSelasa->isPiketToday());
        $this->assertFalse($this->guruSelasa->isPiketOnDate());

        // Scope hariIni otomatis mengembalikan guru piket hari Senin
        $todayDutyTeachers = DutySchedule::hariIni()->pluck('teacher_id')->toArray();
        $this->assertContains($this->guruSenin->id, $todayDutyTeachers);
        $this->assertNotContains($this->guruSelasa->id, $todayDutyTeachers);
    }

    /**
     * Test pergantian hari otomatis mengaktifkan guru piket hari berikutnya.
     */
    public function test_teacher_rotation_switches_automatically_when_day_changes(): void
    {
        DutySchedule::create([
            'teacher_id' => $this->guruSenin->id,
            'hari' => 'Senin',
            'tanggal' => null,
            'institution_id' => $this->institution->id,
        ]);

        DutySchedule::create([
            'teacher_id' => $this->guruSelasa->id,
            'hari' => 'Selasa',
            'tanggal' => null,
            'institution_id' => $this->institution->id,
        ]);

        // Ganti hari menjadi hari Selasa (3 November 2026 adalah Selasa)
        Carbon::setTestNow('2026-11-03 08:00:00');
        $this->assertEquals('Selasa', Schedule::getIndonesianDayName(Carbon::today()));

        // Guru Selasa otomatis bertugas
        $this->assertTrue($this->guruSelasa->isPiketToday());

        // Guru Senin sekarang tidak bertugas
        $this->assertFalse($this->guruSenin->isPiketToday());

        // Scope hariIni otomatis beralih ke Guru Selasa
        $todayDutyTeachers = DutySchedule::hariIni()->pluck('teacher_id')->toArray();
        $this->assertContains($this->guruSelasa->id, $todayDutyTeachers);
        $this->assertNotContains($this->guruSenin->id, $todayDutyTeachers);
    }

    /**
     * Test kompatibilitas dengan jadwal lama yang masih menggunakan tanggal spesifik.
     */
    public function test_backward_compatibility_with_date_specific_schedules(): void
    {
        // Buat jadwal dengan tanggal spesifik Rabu, 4 November 2026
        DutySchedule::create([
            'teacher_id' => $this->guruRabu->id,
            'hari' => 'Rabu',
            'tanggal' => '2026-11-04',
            'institution_id' => $this->institution->id,
        ]);

        // Simulasikan hari Rabu, 4 November 2026
        Carbon::setTestNow('2026-11-04 09:00:00');

        $this->assertTrue($this->guruRabu->isPiketToday());
        $this->assertTrue($this->guruRabu->isPiketOnDate('2026-11-04'));

        $todayDutyTeachers = DutySchedule::hariIni()->pluck('teacher_id')->toArray();
        $this->assertContains($this->guruRabu->id, $todayDutyTeachers);
    }

    /**
     * Test kebijakan / policy kesiswaan mengenali guru piket berbasis hari.
     */
    public function test_attendance_and_violation_policies_recognize_day_based_piket(): void
    {
        DutySchedule::create([
            'teacher_id' => $this->guruSenin->id,
            'hari' => 'Senin',
            'tanggal' => null,
            'institution_id' => $this->institution->id,
        ]);

        // Simulasikan hari Senin
        Carbon::setTestNow('2026-11-02 07:45:00');

        $attendancePolicy = new DailyAttendancePolicy();
        $violationPolicy = new AttributeViolationPolicy();
        $prayerPolicy = new PrayerAttendancePolicy();

        // Guru Senin diakui memiliki hak akses piket hari ini
        $this->assertTrue($attendancePolicy->create($this->guruSenin));
        $this->assertTrue($violationPolicy->create($this->guruSenin));
        $this->assertTrue($prayerPolicy->create($this->guruSenin));

        // Guru Selasa yang tidak piket hari Senin tidak memiliki hak akses piket
        $this->assertFalse($violationPolicy->create($this->guruSelasa));
    }
}
