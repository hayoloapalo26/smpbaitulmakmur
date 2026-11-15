<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\FingerspotAttendanceController;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FingerFirstScanLockingTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected Classroom $classroom;
    protected Student $siswaBiasa;
    protected Student $siswaLana;
    protected FingerspotAttendanceController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        Institution::firstOrCreate(['id' => 1], [
            'name' => 'Yayasan Baitul Makmur',
            'slug' => 'yayasan',
        ]);

        $this->institution = Institution::firstOrCreate(['id' => 2], [
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smpi',
        ]);

        $waliKelas = User::create([
            'name'           => 'Wali Kelas 7A',
            'email'          => 'wali7a@example.com',
            'password'       => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);

        $this->classroom = Classroom::create([
            'name'           => '7-A',
            'tingkat'        => '7',
            'wali_kelas_id'  => $waliKelas->id,
            'institution_id' => $this->institution->id,
            'tipe'           => Classroom::TIPE_REGULER,
        ]);

        // 1. Siswa biasa: Ahmad (PIN 21)
        $this->siswaBiasa = Student::create([
            'name'           => 'Ahmad Fathoni',
            'nis'            => '1001',
            'nisn'           => '0011223344',
            'tanggal_lahir'  => '2010-01-01',
            'classroom_id'   => $this->classroom->id,
            'institution_id' => $this->institution->id,
            'jenis_kelamin'  => 'L',
            'id_finger'      => '21',
        ]);

        // 2. Siswa testing: Lana (PIN 78)
        $this->siswaLana = Student::create([
            'name'           => 'Lana Tester',
            'nis'            => '1002',
            'nisn'           => '0011223355',
            'tanggal_lahir'  => '2010-01-01',
            'classroom_id'   => $this->classroom->id,
            'institution_id' => $this->institution->id,
            'jenis_kelamin'  => 'L',
            'id_finger'      => '78',
        ]);

        $this->controller = new FingerspotAttendanceController();
    }

    public function test_kedatangan_finger_pertama_kali_dikunci_dan_finger_kedua_ditolak(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Scan Pertama: Jam 06:40:00 (Tepat Waktu)
        $scan1 = $this->controller->processAttendanceRecord(
            '21',
            "{$today} 06:40:00"
        );

        $this->assertEquals('success', $scan1['status']);
        $this->assertEquals('masuk', $scan1['tipe']);
        $this->assertFalse($scan1['is_late']);

        // Verifikasi tersimpan di database
        $this->assertDatabaseHas('daily_attendances', [
            'student_id'       => $this->siswaBiasa->id,
            'tanggal'          => $today,
            'tipe_absen'       => 'masuk',
            'waktu_kedatangan' => '06:40:00',
            'is_late'          => false,
        ]);

        // 2. Scan Kedua (Iseng finger lagi): Jam 06:48:00
        $scan2 = $this->controller->processAttendanceRecord(
            '21',
            "{$today} 06:48:00"
        );

        $this->assertEquals('duplicate', $scan2['status']);
        $this->assertTrue($scan2['locked']);
        $this->assertEquals('06:40:00', $scan2['first_scan_time']);

        // 3. Scan Ketiga (Iseng finger lagi saat jam terlambat): Jam 07:15:00
        $scan3 = $this->controller->processAttendanceRecord(
            '21',
            "{$today} 07:15:00"
        );

        $this->assertEquals('duplicate', $scan3['status']);
        $this->assertTrue($scan3['locked']);

        // Data presensi kedatangan di database TETAP 1 record dan TETAP mengunci jam 06:40:00 (Tepat Waktu)
        $records = DailyAttendance::where('student_id', $this->siswaBiasa->id)
            ->where('tanggal', $today)
            ->where('tipe_absen', 'masuk')
            ->get();

        $this->assertCount(1, $records);
        $this->assertEquals('06:40:00', $records->first()->waktu_kedatangan);
        $this->assertFalse($records->first()->is_late);

        // Student::getAttendanceOnDate() mengembalikan scan pertama
        $att = $this->siswaBiasa->getAttendanceOnDate($today, 'masuk');
        $this->assertNotNull($att);
        $this->assertEquals('06:40:00', $att->waktu_kedatangan);
    }

    public function test_sholat_dhuhur_finger_pertama_kali_dikunci_dan_finger_kedua_ditolak(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Scan Pertama Dhuhur: Jam 12:05:00 (Tepat Waktu, batas 12:15)
        $scan1 = $this->controller->processAttendanceRecord(
            '21',
            "{$today} 12:05:00"
        );

        $this->assertEquals('success', $scan1['status']);
        $this->assertEquals('sholat_dhuhur', $scan1['tipe']);
        $this->assertFalse($scan1['is_late']);

        // 2. Scan Kedua Dhuhur (Iseng finger lagi): Jam 12:12:00
        $scan2 = $this->controller->processAttendanceRecord(
            '21',
            "{$today} 12:12:00"
        );

        $this->assertEquals('duplicate', $scan2['status']);
        $this->assertTrue($scan2['locked']);
        $this->assertEquals('12:05:00', $scan2['first_scan_time']);

        // Data dhuhur tetap 1 record terkunci pada 12:05:00
        $records = DailyAttendance::where('student_id', $this->siswaBiasa->id)
            ->where('tanggal', $today)
            ->where('tipe_absen', 'sholat_dhuhur')
            ->get();

        $this->assertCount(1, $records);
        $this->assertEquals('12:05:00', $records->first()->waktu_kedatangan);
    }

    public function test_sholat_ashar_finger_pertama_kali_dikunci_dan_finger_kedua_ditolak(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Scan Pertama Ashar: Jam 15:05:00 (Tepat Waktu, batas 15:15)
        $scan1 = $this->controller->processAttendanceRecord(
            '21',
            "{$today} 15:05:00"
        );

        $this->assertEquals('success', $scan1['status']);
        $this->assertEquals('sholat_ashar', $scan1['tipe']);
        $this->assertFalse($scan1['is_late']);

        // 2. Scan Kedua Ashar (Iseng finger lagi): Jam 15:20:00
        $scan2 = $this->controller->processAttendanceRecord(
            '21',
            "{$today} 15:20:00"
        );

        $this->assertEquals('duplicate', $scan2['status']);
        $this->assertTrue($scan2['locked']);
        $this->assertEquals('15:05:00', $scan2['first_scan_time']);

        // Data ashar tetap 1 record terkunci pada 15:05:00
        $records = DailyAttendance::where('student_id', $this->siswaBiasa->id)
            ->where('tanggal', $today)
            ->whereIn('tipe_absen', ['sholat_ashar', 'pulang'])
            ->get();

        $this->assertCount(1, $records);
        $this->assertEquals('15:05:00', $records->first()->waktu_kedatangan);
    }

    public function test_siswa_bernama_lana_dikecualikan_untuk_keperluan_testing(): void
    {
        $today = Carbon::today()->toDateString();

        // Scan 1 Lana: 06:40:00
        $scan1 = $this->controller->processAttendanceRecord(
            '78',
            "{$today} 06:40:00"
        );
        $this->assertEquals('success', $scan1['status']);

        // Scan 2 Lana pada menit berbeda untuk testing: 06:45:00
        $scan2 = $this->controller->processAttendanceRecord(
            '78',
            "{$today} 06:45:00"
        );
        $this->assertEquals('success', $scan2['status']);

        // Untuk Lana, kedua record tersimpan karena akun uji coba (trial/testing)
        $lanaRecords = DailyAttendance::where('student_id', $this->siswaLana->id)
            ->where('tanggal', $today)
            ->where('tipe_absen', 'masuk')
            ->count();

        $this->assertGreaterThanOrEqual(2, $lanaRecords);
    }
}
