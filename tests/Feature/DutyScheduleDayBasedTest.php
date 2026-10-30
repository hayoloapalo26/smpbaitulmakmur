<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\AbsensiManual;
use App\Filament\Staff\Pages\PiketDashboard;
use App\Filament\Staff\Resources\DailyAttendanceResource;
use App\Filament\Staff\Resources\DutyScheduleResource;
use App\Filament\Staff\Resources\DutyScheduleResource\Pages\CreateDutySchedule;
use App\Filament\Staff\Resources\DutySwapRequestResource;
use App\Models\AcademicYear;
use App\Models\DutySchedule;
use App\Models\DutySwapRequest;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\User;
use App\Policies\AttributeViolationPolicy;
use App\Policies\DailyAttendancePolicy;
use App\Policies\PrayerAttendancePolicy;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

        Filament::setCurrentPanel(Filament::getPanel('staff'));
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
     * Test input guru piket di satu hari dengan Multiple Choice (lebih dari 1 guru sekaligus).
     */
    public function test_duty_schedule_multiple_choice_teacher_input(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        Livewire::test(CreateDutySchedule::class)
            ->fillForm([
                'hari' => 'Senin',
                'teacher_ids' => [$this->guruSenin->id, $this->guruSelasa->id],
                'notes' => 'Piket gabungan 2 guru hari Senin',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Kedua guru harus terdaftar di jadwal hari Senin
        $this->assertDatabaseHas('duty_schedules', [
            'hari' => 'Senin',
            'teacher_id' => $this->guruSenin->id,
        ]);

        $this->assertDatabaseHas('duty_schedules', [
            'hari' => 'Senin',
            'teacher_id' => $this->guruSelasa->id,
        ]);
    }

    /**
     * Test guru otomatis bertugas saat hari berjalan cocok dengan hari penugasan tanpa perlu tanggal.
     */
    public function test_teacher_automatically_becomes_piket_today_when_day_matches(): void
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

        // Simulasikan hari Senin (2 November 2026 adalah Senin)
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
     * Test fitur guru piket (Dashboard, Absensi Manual, dll) HANYA MUNCUL ketika beliaunya bertugas di harinya.
     */
    public function test_piket_features_only_accessible_when_teacher_is_on_duty_today(): void
    {
        DutySchedule::create([
            'teacher_id' => $this->guruSenin->id,
            'hari' => 'Senin',
            'tanggal' => null,
            'institution_id' => $this->institution->id,
        ]);

        DutySchedule::create([
            'teacher_id' => $this->guruRabu->id,
            'hari' => 'Rabu',
            'tanggal' => null,
            'institution_id' => $this->institution->id,
        ]);

        // Hari Senin: Guru Senin piket, Guru Rabu tidak piket
        Carbon::setTestNow('2026-11-02 08:00:00');

        $this->actingAs($this->guruSenin);
        $this->assertTrue(PiketDashboard::canAccess());
        $this->assertTrue(AbsensiManual::canAccess());
        $this->assertTrue(DailyAttendanceResource::canAccess());

        $this->actingAs($this->guruRabu);
        $this->assertFalse(PiketDashboard::canAccess());
        $this->assertFalse(AbsensiManual::canAccess());
        $this->assertFalse(DailyAttendanceResource::canAccess());
    }

    /**
     * Test fitur tukar jadwal piket benar-benar berfungsi dan terintegrasi realtime.
     */
    public function test_approved_duty_swap_seamlessly_transfers_duty_and_access(): void
    {
        DutySchedule::create([
            'teacher_id' => $this->guruSenin->id,
            'hari' => 'Senin',
            'tanggal' => null,
            'institution_id' => $this->institution->id,
        ]);

        DutySchedule::create([
            'teacher_id' => $this->guruRabu->id,
            'hari' => 'Rabu',
            'tanggal' => null,
            'institution_id' => $this->institution->id,
        ]);

        // Pengajuan tukar piket: Guru Senin bertukar dengan Guru Rabu
        // Guru Senin digantikan oleh Guru Rabu pada tanggal 2026-11-02 (Senin)
        // Guru Senin menggantikan Guru Rabu pada tanggal 2026-11-04 (Rabu)
        $swap = DutySwapRequest::create([
            'requester_id' => $this->guruSenin->id,
            'original_date' => '2026-11-02',
            'substitute_teacher_id' => $this->guruRabu->id,
            'new_date' => '2026-11-04',
            'status' => 'disetujui', // Status telah disetujui
            'institution_id' => $this->institution->id,
        ]);

        // 1. UJI PADA HARI SENIN (2026-11-02):
        Carbon::setTestNow('2026-11-02 08:00:00');

        // Guru Senin (yang menukar keluar) TIDAK piket hari Senin
        $this->assertFalse($this->guruSenin->isPiketToday());
        $this->actingAs($this->guruSenin);
        $this->assertFalse(PiketDashboard::canAccess());
        $this->assertFalse(AbsensiManual::canAccess());

        // Guru Rabu (pengganti) AKTIF piket hari Senin
        $this->assertTrue($this->guruRabu->isPiketToday());
        $this->actingAs($this->guruRabu);
        $this->assertTrue(PiketDashboard::canAccess());
        $this->assertTrue(AbsensiManual::canAccess());

        // 2. UJI PADA HARI RABU (2026-11-04):
        Carbon::setTestNow('2026-11-04 08:00:00');

        // Guru Rabu (yang jadwalnya ditukar ke Guru Senin) TIDAK piket hari Rabu
        $this->assertFalse($this->guruRabu->isPiketToday());
        $this->actingAs($this->guruRabu);
        $this->assertFalse(PiketDashboard::canAccess());

        // Guru Senin (yang menggantikan pada new_date) AKTIF piket hari Rabu
        $this->assertTrue($this->guruSenin->isPiketToday());
        $this->actingAs($this->guruSenin);
        $this->assertTrue(PiketDashboard::canAccess());
    }

    /**
     * Test navigasi tukar jadwal piket disatukan (tidak muncul terpisah di sidebar).
     */
    public function test_duty_swap_request_resource_navigation_is_unified(): void
    {
        $this->assertFalse(
            DutySwapRequestResource::shouldRegisterNavigation(),
            'Resource DutySwapRequest tidak boleh terdaftar mandiri di sidebar (harus terpadu di menu Guru Piket)'
        );
    }

    /**
     * Test kompatibilitas dengan jadwal lama yang masih menggunakan tanggal spesifik.
     */
    public function test_backward_compatibility_with_date_specific_schedules(): void
    {
        DutySchedule::create([
            'teacher_id' => $this->guruRabu->id,
            'hari' => 'Rabu',
            'tanggal' => '2026-11-04',
            'institution_id' => $this->institution->id,
        ]);

        Carbon::setTestNow('2026-11-04 09:00:00');

        $this->assertTrue($this->guruRabu->isPiketToday());
        $this->assertTrue($this->guruRabu->isPiketOnDate('2026-11-04'));

        $todayDutyTeachers = DutySchedule::hariIni()->pluck('teacher_id')->toArray();
        $this->assertContains($this->guruRabu->id, $todayDutyTeachers);
    }
}
