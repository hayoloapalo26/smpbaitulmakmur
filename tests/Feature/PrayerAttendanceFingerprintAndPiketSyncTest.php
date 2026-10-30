<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\AbsensiManual;
use App\Http\Controllers\Api\FingerspotAttendanceController;
use App\Http\Controllers\Api\HikvisionAttendanceController;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Institution;
use App\Models\PrayerAttendance;
use App\Models\Student;
use App\Models\StudentHalangan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrayerAttendanceFingerprintAndPiketSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected Classroom $classroom;
    protected Student $studentMale1;
    protected Student $studentMale2;
    protected Student $studentFemale;
    protected User $guruPiket;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);

        Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->institution = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        $this->classroom = Classroom::create([
            'name'           => 'VII-A',
            'tingkat'        => 7,
            'institution_id' => $this->institution->id,
        ]);

        $this->studentMale1 = Student::create([
            'name'           => 'Ahmad Zaki',
            'nis'            => '2001',
            'fingerspot_pin' => '2001',
            'classroom_id'   => $this->classroom->id,
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2012-01-10',
            'institution_id' => $this->institution->id,
        ]);

        $this->studentMale2 = Student::create([
            'name'           => 'Fikri Rahman',
            'nis'            => '2002',
            'fingerspot_pin' => '2002',
            'classroom_id'   => $this->classroom->id,
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2012-02-15',
            'institution_id' => $this->institution->id,
        ]);

        $this->studentFemale = Student::create([
            'name'           => 'Fatimah Zahra',
            'nis'            => '2003',
            'fingerspot_pin' => '2003',
            'classroom_id'   => $this->classroom->id,
            'jenis_kelamin'  => 'P',
            'tanggal_lahir'  => '2012-03-20',
            'institution_id' => $this->institution->id,
        ]);

        $this->guruPiket = User::create([
            'name'           => 'Ustadz Ahmad Piket',
            'email'          => 'ahmad.piket@smpi.test',
            'password'       => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);
        $this->guruPiket->assignRole('guru_piket');

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));
    }

    /**
     * 1. Test Guru Piket menginput Sakit -> Otomatis 1 hari merekap sholat Dhuhur & Ashar sebagai Sakit
     */
    public function test_guru_piket_input_sakit_otomatis_merekap_sholat_dhuhur_dan_ashar_1_hari(): void
    {
        $today = Carbon::today()->toDateString();

        // Guru Piket mencatat Ahmad Zaki Sakit saat presensi pagi
        DailyAttendance::create([
            'student_id'     => $this->studentMale1->id,
            'institution_id' => $this->institution->id,
            'teacher_id'     => $this->guruPiket->id,
            'tanggal'        => $today,
            'tipe_absen'     => 'masuk',
            'metode_absen'   => 'manual',
            'status'         => 'sakit',
            'catatan'        => 'Demam tinggi berobat ke dokter',
        ]);

        // Verifikasi PrayerAttendance otomatis tercatat Dhuhur & Ashar = sakit
        $prayerDhuhur = PrayerAttendance::where('student_id', $this->studentMale1->id)
            ->where('tanggal', $today)
            ->where('jenis_sholat', 'Dhuhur')
            ->first();

        $this->assertNotNull($prayerDhuhur);
        $this->assertEquals('sakit', $prayerDhuhur->status);

        $prayerAshar = PrayerAttendance::where('student_id', $this->studentMale1->id)
            ->where('tanggal', $today)
            ->where('jenis_sholat', 'Ashar')
            ->first();

        $this->assertNotNull($prayerAshar);
        $this->assertEquals('sakit', $prayerAshar->status);

        // Verifikasi DailyAttendance sholat juga tercatat sakit
        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->studentMale1->id,
            'tanggal'    => $today,
            'tipe_absen' => 'sholat_dhuhur',
            'status'     => 'sakit',
        ]);

        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->studentMale1->id,
            'tanggal'    => $today,
            'tipe_absen' => 'sholat_ashar',
            'status'     => 'sakit',
        ]);
    }

    /**
     * 2. Test Guru Piket menginput Izin dan Alpa -> Otomatis 1 hari merekap sholat Dhuhur & Ashar
     */
    public function test_guru_piket_input_izin_dan_alpa_otomatis_merekap_sholat_dhuhur_dan_ashar(): void
    {
        $today = Carbon::today()->toDateString();

        // Siswa 1: Izin
        DailyAttendance::create([
            'student_id'     => $this->studentMale1->id,
            'institution_id' => $this->institution->id,
            'teacher_id'     => $this->guruPiket->id,
            'tanggal'        => $today,
            'tipe_absen'     => 'masuk',
            'metode_absen'   => 'manual',
            'status'         => 'izin',
            'catatan'        => 'Acara keluarga',
        ]);

        // Siswa 2: Alpa (Tanpa Keterangan)
        DailyAttendance::create([
            'student_id'     => $this->studentMale2->id,
            'institution_id' => $this->institution->id,
            'teacher_id'     => $this->guruPiket->id,
            'tanggal'        => $today,
            'tipe_absen'     => 'masuk',
            'metode_absen'   => 'manual',
            'status'         => 'alpa',
        ]);

        // Verifikasi Siswa 1 (Izin)
        $this->assertEquals('izin', PrayerAttendance::where('student_id', $this->studentMale1->id)->where('tanggal', $today)->where('jenis_sholat', 'Dhuhur')->value('status'));
        $this->assertEquals('izin', PrayerAttendance::where('student_id', $this->studentMale1->id)->where('tanggal', $today)->where('jenis_sholat', 'Ashar')->value('status'));

        // Verifikasi Siswa 2 (Alpa)
        $this->assertEquals('alpa', PrayerAttendance::where('student_id', $this->studentMale2->id)->where('tanggal', $today)->where('jenis_sholat', 'Dhuhur')->value('status'));
        $this->assertEquals('alpa', PrayerAttendance::where('student_id', $this->studentMale2->id)->where('tanggal', $today)->where('jenis_sholat', 'Ashar')->value('status'));
    }

    /**
     * 3. Test Guru Piket menginput Siswi Halangan / Haid -> Otomatis 1 hari merekap sholat sebagai Halangan
     */
    public function test_guru_piket_input_siswi_halangan_otomatis_merekap_sholat_dhuhur_dan_ashar(): void
    {
        $today = Carbon::today()->toDateString();

        // Guru piket mencatat siswi halangan
        StudentHalangan::create([
            'student_id'      => $this->studentFemale->id,
            'institution_id'  => $this->institution->id,
            'tanggal_mulai'   => $today,
            'tanggal_selesai' => $today,
            'catatan'         => 'Hari ke-1 Udzur Syar\'i',
            'recorded_by'     => $this->guruPiket->id,
        ]);

        // Verifikasi kedua sholat otomatis terekap 'halangan'
        $prayerDhuhur = PrayerAttendance::where('student_id', $this->studentFemale->id)
            ->where('tanggal', $today)
            ->where('jenis_sholat', 'Dhuhur')
            ->first();
        $this->assertNotNull($prayerDhuhur);
        $this->assertEquals('halangan', $prayerDhuhur->status);

        $prayerAshar = PrayerAttendance::where('student_id', $this->studentFemale->id)
            ->where('tanggal', $today)
            ->where('jenis_sholat', 'Ashar')
            ->first();
        $this->assertNotNull($prayerAshar);
        $this->assertEquals('halangan', $prayerAshar->status);

        // Verifikasi DailyAttendance juga terekap halangan
        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->studentFemale->id,
            'tanggal'    => $today,
            'tipe_absen' => 'sholat_dhuhur',
            'status'     => 'halangan',
        ]);
        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->studentFemale->id,
            'tanggal'    => $today,
            'tipe_absen' => 'sholat_ashar',
            'status'     => 'halangan',
        ]);
    }

    /**
     * 4. Test Kombinasi 4 Waktu Fingerprint: Pagi (<12:00), Dhuhur (12:00-13:59), Ashar (14:00-15:59), Pulang (>=16:00)
     */
    public function test_mesin_fingerprint_4_waktu_jam_dan_sinkronisasi_sholat_dhuhur_dan_ashar(): void
    {
        $controller = new FingerspotAttendanceController();
        $todayStr = Carbon::today()->toDateString();

        // Waktu 1: Pagi (06:50) -> Kehadiran Pagi (Masuk)
        $pagiResult = $controller->processAttendanceRecord(
            $this->studentMale1->fingerspot_pin,
            "{$todayStr} 06:50:00",
            1
        );
        $this->assertEquals('success', $pagiResult['status']);
        $this->assertEquals('masuk', $pagiResult['tipe']);
        $this->assertDatabaseHas('daily_attendances', [
            'student_id'   => $this->studentMale1->id,
            'tanggal'      => $todayStr,
            'tipe_absen'   => 'masuk',
            'metode_absen' => 'fingerprint',
            'status'       => 'hadir',
        ]);

        // Waktu 2: Sholat Dhuhur (12:15) -> Absensi Sholat Dhuhur Berjamaah
        $dhuhurResult = $controller->processAttendanceRecord(
            $this->studentMale1->fingerspot_pin,
            "{$todayStr} 12:15:30",
            1
        );
        $this->assertEquals('success', $dhuhurResult['status']);
        $this->assertEquals('sholat_dhuhur', $dhuhurResult['tipe']);
        $this->assertDatabaseHas('daily_attendances', [
            'student_id'   => $this->studentMale1->id,
            'tanggal'      => $todayStr,
            'tipe_absen'   => 'sholat_dhuhur',
            'metode_absen' => 'fingerprint',
            'status'       => 'hadir',
        ]);
        // Otomatis tersinkron ke PrayerAttendance Dhuhur = hadir
        $this->assertDatabaseHas('prayer_attendances', [
            'student_id'   => $this->studentMale1->id,
            'tanggal'      => $todayStr,
            'jenis_sholat' => 'Dhuhur',
            'status'       => 'hadir',
        ]);

        // Waktu 3: Sholat Ashar (14:30) -> Absensi Sholat Ashar Berjamaah
        $asharResult = $controller->processAttendanceRecord(
            $this->studentMale1->fingerspot_pin,
            "{$todayStr} 14:30:15",
            1
        );
        $this->assertEquals('success', $asharResult['status']);
        $this->assertEquals('sholat_ashar', $asharResult['tipe']);
        $this->assertDatabaseHas('daily_attendances', [
            'student_id'   => $this->studentMale1->id,
            'tanggal'      => $todayStr,
            'tipe_absen'   => 'sholat_ashar',
            'metode_absen' => 'fingerprint',
            'status'       => 'hadir',
        ]);
        // Otomatis tersinkron ke PrayerAttendance Ashar = hadir
        $this->assertDatabaseHas('prayer_attendances', [
            'student_id'   => $this->studentMale1->id,
            'tanggal'      => $todayStr,
            'jenis_sholat' => 'Ashar',
            'status'       => 'hadir',
        ]);

        // Waktu 4: Pulang (16:10) -> Kepulangan
        $pulangResult = $controller->processAttendanceRecord(
            $this->studentMale1->fingerspot_pin,
            "{$todayStr} 16:10:00",
            1
        );
        $this->assertEquals('success', $pulangResult['status']);
        $this->assertEquals('pulang', $pulangResult['tipe']);
        $this->assertDatabaseHas('daily_attendances', [
            'student_id'   => $this->studentMale1->id,
            'tanggal'      => $todayStr,
            'tipe_absen'   => 'pulang',
            'metode_absen' => 'fingerprint',
            'status'       => 'hadir',
        ]);
    }

    /**
     * 5. Test scan fingerprint TIDAK MENIMPA siswa yang sudah tercatat Sakit, Izin, Alpa, atau Halangan
     */
    public function test_scan_fingerprint_tidak_menimpa_siswa_yang_sudah_sakit_izin_alpa_atau_halangan(): void
    {
        $controller = new FingerspotAttendanceController();
        $todayStr = Carbon::today()->toDateString();

        // 1. Siswa sakit diinput guru piket di pagi hari
        DailyAttendance::create([
            'student_id'     => $this->studentMale1->id,
            'institution_id' => $this->institution->id,
            'teacher_id'     => $this->guruPiket->id,
            'tanggal'        => $todayStr,
            'tipe_absen'     => 'masuk',
            'metode_absen'   => 'manual',
            'status'         => 'sakit',
        ]);

        // Scan terjadi pada jam Dhuhur (12:20)
        $scanResult = $controller->processAttendanceRecord(
            $this->studentMale1->fingerspot_pin,
            "{$todayStr} 12:20:00",
            1
        );

        // Status sholat TETAP sakit, tidak tertimpa menjadi hadir
        $this->assertContains($scanResult['status'], ['excused', 'duplicate']);
        $this->assertEquals('sakit', PrayerAttendance::where('student_id', $this->studentMale1->id)->where('tanggal', $todayStr)->where('jenis_sholat', 'Dhuhur')->value('status'));
        $this->assertEquals('sakit', DailyAttendance::where('student_id', $this->studentMale1->id)->where('tanggal', $todayStr)->where('tipe_absen', 'sholat_dhuhur')->value('status'));

        // 2. Siswi halangan
        StudentHalangan::create([
            'student_id'      => $this->studentFemale->id,
            'institution_id'  => $this->institution->id,
            'tanggal_mulai'   => $todayStr,
            'tanggal_selesai' => $todayStr,
            'recorded_by'     => $this->guruPiket->id,
        ]);

        // Siswi scan saat jam Ashar (14:45)
        $scanSiswi = $controller->processAttendanceRecord(
            $this->studentFemale->fingerspot_pin,
            "{$todayStr} 14:45:00",
            1
        );

        // Status sholat TETAP halangan, tidak tertimpa menjadi hadir
        $this->assertEquals('halangan', PrayerAttendance::where('student_id', $this->studentFemale->id)->where('tanggal', $todayStr)->where('jenis_sholat', 'Ashar')->value('status'));
        $this->assertEquals('halangan', DailyAttendance::where('student_id', $this->studentFemale->id)->where('tanggal', $todayStr)->where('tipe_absen', 'sholat_ashar')->value('status'));
    }

    /**
     * 6. Test integrasi halaman AbsensiManual saat Guru Piket membuka slot sholat
     */
    public function test_absensi_manual_page_supports_sholat_and_shows_piket_badges(): void
    {
        $todayStr = Carbon::today()->toDateString();

        // Siswa 1 diinput Izin pagi hari
        DailyAttendance::create([
            'student_id'     => $this->studentMale1->id,
            'institution_id' => $this->institution->id,
            'teacher_id'     => $this->guruPiket->id,
            'tanggal'        => $todayStr,
            'tipe_absen'     => 'masuk',
            'metode_absen'   => 'manual',
            'status'         => 'izin',
        ]);

        // Siswi diinput Halangan
        StudentHalangan::create([
            'student_id'      => $this->studentFemale->id,
            'institution_id'  => $this->institution->id,
            'tanggal_mulai'   => $todayStr,
            'tanggal_selesai' => $todayStr,
            'recorded_by'     => $this->guruPiket->id,
        ]);

        // Guru Piket membuka AbsensiManual untuk Sholat Dhuhur
        Livewire::actingAs($this->guruPiket)
            ->test(AbsensiManual::class)
            ->set('selectedClassroomId', $this->classroom->id)
            ->set('tipeAbsen', 'sholat_dhuhur')
            ->set('selectedDate', $todayStr)
            ->call('loadAttendance')
            ->assertSet("attendanceData.{$this->studentMale1->id}", 'izin')
            ->assertSet("attendanceData.{$this->studentFemale->id}", 'halangan');
    }
}
