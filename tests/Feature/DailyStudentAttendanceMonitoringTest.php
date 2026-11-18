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
     * 4c. Test PDF Presensi Harian memuat kedua guru piket yang disetting oleh Waka Kesiswaan.
     */
    public function test_daily_attendance_pdf_integrates_two_duty_teachers_from_schedule(): void
    {
        $today = Carbon::today();
        $todayIndo = Schedule::getIndonesianDayName($today);

        // Guru Piket 1 & 2
        $guruPiket1 = User::create([
            'name'           => 'Nadila Choirunnisa Intsani, M.Pd',
            'email'          => 'nadila@smpi.test',
            'password'       => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);
        $guruPiket1->assignRole('guru_mapel');

        $guruPiket2 = User::create([
            'name'           => 'Indah Wahyuningtyas, M.Pd',
            'email'          => 'indah@smpi.test',
            'password'       => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);
        $guruPiket2->assignRole('guru_mapel');

        // Daftarkan ke DutySchedule untuk hari ini
        DutySchedule::create([
            'teacher_id'     => $guruPiket1->id,
            'institution_id' => $this->institution->id,
            'hari'           => $todayIndo,
            'semester'       => ($today->month >= 7 && $today->month <= 12) ? 'Ganjil' : 'Genap',
        ]);
        DutySchedule::create([
            'teacher_id'     => $guruPiket2->id,
            'institution_id' => $this->institution->id,
            'hari'           => $todayIndo,
            'semester'       => ($today->month >= 7 && $today->month <= 12) ? 'Ganjil' : 'Genap',
        ]);

        // Verifikasi getDutyTeachersForDate mengembalikan 2 guru piket yang diset
        $dutyTeachers = DutySchedule::getDutyTeachersForDate($today->toDateString(), $this->institution->id);
        $this->assertGreaterThanOrEqual(2, $dutyTeachers->count());
        $this->assertTrue($dutyTeachers->contains('name', 'Nadila Choirunnisa Intsani, M.Pd'));
        $this->assertTrue($dutyTeachers->contains('name', 'Indah Wahyuningtyas, M.Pd'));

        // Akses endpoint PDF presensi harian
        $this->actingAs($guruPiket2);
        $response = $this->get(route('daily-attendance.rekap.pdf', [
            'mode'    => 'harian',
            'tanggal' => $today->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
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

    /**
     * 6. Test Fitur Tandai Halangan: Sholat Dhuhur & Ashar otomatis Halangan, sedangkan Kedatangan tetap normal.
     */
    public function test_tandai_halangan_records_prayer_as_halangan_and_leaves_kedatangan_untouched(): void
    {
        $this->actingAs($this->guruPiketToday);
        $today = Carbon::today()->toDateString();

        // Kedatangan siswi di pagi hari berhasil scan finger tepat waktu
        DailyAttendance::create([
            'student_id'       => $this->studentFemale->id,
            'institution_id'   => $this->institution->id,
            'tanggal'          => $today,
            'tipe_absen'       => 'masuk',
            'waktu_kedatangan' => '06:45:00',
            'status'           => 'hadir',
            'is_late'          => false,
            'metode_absen'     => 'fingerprint',
        ]);

        // Guru piket menandai siswi sedang halangan
        StudentHalangan::create([
            'student_id'      => $this->studentFemale->id,
            'institution_id'  => $this->institution->id,
            'tanggal_mulai'   => $today,
            'tanggal_selesai' => Carbon::today()->addDays(6)->toDateString(),
            'catatan'         => "Sedang halangan / udzur syar'i",
            'recorded_by'     => $this->guruPiketToday->id,
        ]);

        // Verifikasi: Kedatangan tetap Hadir
        $attMasuk = $this->studentFemale->getAttendanceOnDate($today, 'masuk');
        $this->assertNotNull($attMasuk);
        $this->assertEquals('hadir', $attMasuk->status);
        $this->assertEquals('06:45:00', $attMasuk->waktu_kedatangan);

        // Verifikasi: Sholat Dhuhur & Ashar otomatis tercatat Halangan
        $attDhuhur = $this->studentFemale->getAttendanceOnDate($today, 'sholat_dhuhur');
        $this->assertNotNull($attDhuhur);
        $this->assertEquals('halangan', $attDhuhur->status);

        $attAshar = $this->studentFemale->getAttendanceOnDate($today, ['sholat_ashar', 'pulang']);
        $this->assertNotNull($attAshar);
        $this->assertEquals('halangan', $attAshar->status);

        // Verifikasi isHalangan
        $this->assertTrue(StudentHalangan::isHalangan($this->studentFemale->id, $today));
    }

    /**
     * 7. Test Fitur Catat Izin: Siswa Sakit, Izin, Alpha otomatis terisi ke Kedatangan, Dhuhur, dan Ashar sesuai yang dipilih.
     */
    public function test_catat_izin_sets_all_three_sessions_to_selected_status_with_matching_keterangan(): void
    {
        $this->actingAs($this->guruPiketToday);
        $today = Carbon::today()->toDateString();

        // 1. Catat Sakit
        $sesiList = ['masuk', 'sholat_dhuhur', 'sholat_ashar'];
        foreach ($sesiList as $sesi) {
            DailyAttendance::updateOrCreate(
                [
                    'student_id' => $this->studentMale->id,
                    'tanggal'    => $today,
                    'tipe_absen' => $sesi,
                ],
                [
                    'institution_id' => $this->institution->id,
                    'teacher_id'     => $this->guruPiketToday->id,
                    'status'         => 'sakit',
                    'metode_absen'   => 'manual',
                    'keterangan'     => 'Demam tinggi berobat ke RS',
                ]
            );
        }

        $attMasukMale = $this->studentMale->getAttendanceOnDate($today, 'masuk');
        $attDhuhurMale = $this->studentMale->getAttendanceOnDate($today, 'sholat_dhuhur');
        $attAsharMale = $this->studentMale->getAttendanceOnDate($today, ['sholat_ashar', 'pulang']);

        $this->assertEquals('sakit', $attMasukMale->status);
        $this->assertEquals('Demam tinggi berobat ke RS', $attMasukMale->keterangan);
        $this->assertEquals('sakit', $attDhuhurMale->status);
        $this->assertEquals('Demam tinggi berobat ke RS', $attDhuhurMale->keterangan);
        $this->assertEquals('sakit', $attAsharMale->status);
        $this->assertEquals('Demam tinggi berobat ke RS', $attAsharMale->keterangan);

        // 2. Catat Izin
        $tomorrow = Carbon::tomorrow()->toDateString();
        foreach ($sesiList as $sesi) {
            DailyAttendance::updateOrCreate(
                [
                    'student_id' => $this->studentMale->id,
                    'tanggal'    => $tomorrow,
                    'tipe_absen' => $sesi,
                ],
                [
                    'institution_id' => $this->institution->id,
                    'teacher_id'     => $this->guruPiketToday->id,
                    'status'         => 'izin',
                    'metode_absen'   => 'manual',
                    'keterangan'     => 'Izin acara keluarga',
                ]
            );
        }

        $attMasukTomorrow = $this->studentMale->getAttendanceOnDate($tomorrow, 'masuk');
        $attDhuhurTomorrow = $this->studentMale->getAttendanceOnDate($tomorrow, 'sholat_dhuhur');
        $attAsharTomorrow = $this->studentMale->getAttendanceOnDate($tomorrow, ['sholat_ashar', 'pulang']);

        $this->assertEquals('izin', $attMasukTomorrow->status);
        $this->assertEquals('Izin acara keluarga', $attMasukTomorrow->keterangan);
        $this->assertEquals('izin', $attDhuhurTomorrow->status);
        $this->assertEquals('Izin acara keluarga', $attDhuhurTomorrow->keterangan);
        $this->assertEquals('izin', $attAsharTomorrow->status);
        $this->assertEquals('Izin acara keluarga', $attAsharTomorrow->keterangan);
    }
}
