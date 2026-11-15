<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\FingerspotDeviceResource;
use App\Filament\Staff\Resources\PresensiIzinSiswaResource;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\DutySchedule;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentHalangan;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DailyStudentAttendanceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected Classroom $classroom;
    protected Student $studentMale;
    protected Student $studentFemale;
    protected User $superadmin;
    protected User $wakaKesiswaan;
    protected User $guruPiketToday;
    protected User $guruPiketOtherDay;
    protected User $guruMapel;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);

        Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->institution = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        $this->classroom = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => 7,
            'institution_id' => $this->institution->id,
        ]);

        $this->studentMale = Student::create([
            'name' => 'Ahmad Fadhil',
            'nis' => '7001',
            'fingerspot_pin' => '7001',
            'classroom_id' => $this->classroom->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-10',
            'institution_id' => $this->institution->id,
            'status_siswa' => 'aktif',
        ]);

        $this->studentFemale = Student::create([
            'name' => 'Fathimah Az-Zahra',
            'nis' => '7002',
            'fingerspot_pin' => '7002',
            'classroom_id' => $this->classroom->id,
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2012-08-15',
            'institution_id' => $this->institution->id,
            'status_siswa' => 'aktif',
        ]);

        $this->superadmin = User::create([
            'name' => 'Superadmin IT',
            'email' => 'admin@baitulmakmur.sch.id',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->superadmin->assignRole('superadmin');

        $this->wakaKesiswaan = User::create([
            'name' => 'Ustadz Kesiswaan',
            'email' => 'kesiswaan@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->wakaKesiswaan->assignRole('waka_kesiswaan');

        // Guru Piket yang bertugas HARI INI
        $this->guruPiketToday = User::create([
            'name' => 'Ustadzah Piket Hari Ini',
            'email' => 'piket.today@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruPiketToday->assignRole('guru_piket');

        $todayIndo = Schedule::getIndonesianDayName(Carbon::today());
        DutySchedule::create([
            'teacher_id'     => $this->guruPiketToday->id,
            'institution_id' => $this->institution->id,
            'hari'           => $todayIndo,
            'status'         => 'aktif',
        ]);

        // Guru Piket yang bertugas di HARI LAIN (Bukan hari ini)
        $this->guruPiketOtherDay = User::create([
            'name' => 'Ustadz Piket Hari Lain',
            'email' => 'piket.other@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruPiketOtherDay->assignRole('guru_piket');

        $otherDayIndo = Schedule::getIndonesianDayName(Carbon::today()->addDays(2));
        DutySchedule::create([
            'teacher_id'     => $this->guruPiketOtherDay->id,
            'institution_id' => $this->institution->id,
            'hari'           => $otherDayIndo,
            'status'         => 'aktif',
        ]);

        $this->guruMapel = User::create([
            'name' => 'Guru Mapel IPA',
            'email' => 'ipa@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruMapel->assignRole('guru_mapel');

        Filament::setCurrentPanel(Filament::getPanel('staff'));
    }

    /**
     * 1. Test isolasi: Kontrol mesin fingerspot (FingerspotDeviceResource) HANYA untuk Superadmin.
     */
    public function test_fingerspot_machine_integration_is_isolated_to_superadmin_only(): void
    {
        // Superadmin BISA mengakses
        $this->actingAs($this->superadmin);
        $this->assertTrue(FingerspotDeviceResource::canAccess());
        $this->assertTrue(FingerspotDeviceResource::shouldRegisterNavigation());

        // Waka Kesiswaan TIDAK BISA mengakses kontrol mesin
        $this->actingAs($this->wakaKesiswaan);
        $this->assertFalse(FingerspotDeviceResource::canAccess());
        $this->assertFalse(FingerspotDeviceResource::shouldRegisterNavigation());

        // Guru Piket TIDAK BISA mengakses kontrol mesin
        $this->actingAs($this->guruPiketToday);
        $this->assertFalse(FingerspotDeviceResource::canAccess());
        $this->assertFalse(FingerspotDeviceResource::shouldRegisterNavigation());

        // Guru Mapel TIDAK BISA mengakses kontrol mesin
        $this->actingAs($this->guruMapel);
        $this->assertFalse(FingerspotDeviceResource::canAccess());
        $this->assertFalse(FingerspotDeviceResource::shouldRegisterNavigation());
    }

    /**
     * 2. Test guru piket HANYA melihat dan mengakses fitur presensi KETIKA BERTUGAS PADA HARI H.
     */
    public function test_guru_piket_can_only_access_daily_attendance_when_on_duty_today(): void
    {
        // 1. Guru piket yang bertugas HARI INI: BISA mengakses & menu muncul
        $this->actingAs($this->guruPiketToday);
        $this->assertTrue($this->guruPiketToday->isPiketToday());
        $this->assertTrue(PresensiIzinSiswaResource::canAccess());
        $this->assertTrue(PresensiIzinSiswaResource::shouldRegisterNavigation());

        // 2. Guru piket yang bertugas di HARI LAIN: TIDAK BISA mengakses & menu TIDAK MUNCUL
        $this->actingAs($this->guruPiketOtherDay);
        $this->assertFalse($this->guruPiketOtherDay->isPiketToday());
        $this->assertFalse(PresensiIzinSiswaResource::canAccess());
        $this->assertFalse(PresensiIzinSiswaResource::shouldRegisterNavigation());

        // 3. Waka Kesiswaan & Superadmin: SELALU bisa mengakses kapan saja
        $this->actingAs($this->wakaKesiswaan);
        $this->assertTrue(PresensiIzinSiswaResource::canAccess());
        $this->assertTrue(PresensiIzinSiswaResource::shouldRegisterNavigation());

        $this->actingAs($this->superadmin);
        $this->assertTrue(PresensiIzinSiswaResource::canAccess());
        $this->assertTrue(PresensiIzinSiswaResource::shouldRegisterNavigation());
    }

    /**
     * 3. Test integrasi 5 variabel harian siswa (Kedatangan, Sholat Dhuhur, Sholat Ashar, Ketepatan, Halangan).
     */
    public function test_student_daily_attendance_and_prayer_fingerprint_variables(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Siswa Putra scan masuk tepat waktu jam 06:40
        DailyAttendance::create([
            'student_id' => $this->studentMale->id,
            'institution_id' => $this->institution->id,
            'tanggal' => $today,
            'tipe_absen' => 'masuk',
            'metode_absen' => 'fingerprint',
            'waktu_kedatangan' => '06:40:00',
            'status' => 'hadir',
            'is_late' => false,
        ]);

        // 2. Siswa Putra scan sholat dhuhur terlambat jam 12:20 (> 12:15)
        DailyAttendance::create([
            'student_id' => $this->studentMale->id,
            'institution_id' => $this->institution->id,
            'tanggal' => $today,
            'tipe_absen' => 'sholat_dhuhur',
            'metode_absen' => 'fingerprint',
            'waktu_kedatangan' => '12:20:00',
            'status' => 'hadir',
            'is_late' => true,
        ]);

        // 3. Siswa Putra scan sholat ashar tepat waktu jam 15:10 (<= 15:15)
        DailyAttendance::create([
            'student_id' => $this->studentMale->id,
            'institution_id' => $this->institution->id,
            'tanggal' => $today,
            'tipe_absen' => 'sholat_ashar',
            'metode_absen' => 'fingerprint',
            'waktu_kedatangan' => '15:10:00',
            'status' => 'hadir',
            'is_late' => false,
        ]);

        // 4. Siswi Putri tercatat Halangan / Udzur Syar'i
        StudentHalangan::create([
            'student_id' => $this->studentFemale->id,
            'tanggal_mulai' => $today,
            'tanggal_selesai' => Carbon::today()->addDays(5)->toDateString(),
            'catatan' => 'Haid',
            'recorded_by' => $this->guruPiketToday->id,
        ]);

        // Verifikasi helper method pada Student
        $attMasukMale = $this->studentMale->getAttendanceOnDate($today, 'masuk');
        $this->assertNotNull($attMasukMale);
        $this->assertEquals('hadir', $attMasukMale->status);
        $this->assertEquals('06:40:00', $attMasukMale->waktu_kedatangan);
        $this->assertFalse((bool) $attMasukMale->is_late);

        $attDhuhurMale = $this->studentMale->getAttendanceOnDate($today, 'sholat_dhuhur');
        $this->assertNotNull($attDhuhurMale);
        $this->assertTrue((bool) $attDhuhurMale->is_late);

        $attAsharMale = $this->studentMale->getAttendanceOnDate($today, 'sholat_ashar');
        $this->assertNotNull($attAsharMale);
        $this->assertFalse((bool) $attAsharMale->is_late);

        // Siswi Putri otomatis terdeteksi halangan
        $this->assertTrue(StudentHalangan::isHalangan($this->studentFemale->id, $today));
    }

    /**
     * 4. Test Cetak & Unduh PDF Rekap Presensi Harian & Rentang Tanggal.
     */
    public function test_daily_attendance_pdf_endpoint_supports_daily_and_date_range(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        // 4a. Mode Harian
        $responseHarian = $this->get(route('daily-attendance.rekap.pdf', [
            'mode' => 'harian',
            'tanggal' => Carbon::today()->toDateString(),
            'classroom_id' => $this->classroom->id,
        ]));

        $responseHarian->assertStatus(200);
        $responseHarian->assertHeader('content-type', 'application/pdf');

        // 4b. Mode Rentang Tanggal (Date Range)
        $responseRentang = $this->get(route('daily-attendance.rekap.pdf', [
            'mode' => 'rentang',
            'tanggal_mulai' => Carbon::today()->startOfMonth()->toDateString(),
            'tanggal_selesai' => Carbon::today()->toDateString(),
            'classroom_id' => $this->classroom->id,
        ]));

        $responseRentang->assertStatus(200);
        $responseRentang->assertHeader('content-type', 'application/pdf');
    }

    /**
     * 5. Test Livewire Page ManagePresensiIzinSiswa mounts successfully and handles tabs & modes without error.
     */
    public function test_manage_presensi_izin_siswa_page_mounts_and_handles_tabs(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        \Livewire\Livewire::test(\App\Filament\Staff\Resources\PresensiIzinSiswaResource\Pages\ManagePresensiIzinSiswa::class)
            ->assertSuccessful()
            ->call('onModeChanged', 'rentang')
            ->assertSuccessful()
            ->call('onModeChanged', 'harian')
            ->assertSuccessful()
            ->call('onDateChanged', Carbon::yesterday()->toDateString())
            ->assertSuccessful()
            ->call('onDateRangeChanged', Carbon::today()->subDays(7)->toDateString(), Carbon::today()->toDateString())
            ->assertSuccessful();
    }
}
