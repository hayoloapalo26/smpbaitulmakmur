<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\AbsensiManual;
use App\Filament\Staff\Pages\PiketDashboard;
use App\Filament\Staff\Resources\DailyAttendanceResource;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\DutySchedule;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PresensiPagiDeletionAndPiketIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guruPiketHariIni;
    protected User $guruBiasaBukanPiket;
    protected User $kepsek;
    protected Classroom $classroom;
    protected Student $studentFingerprint;
    protected Student $studentManual;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->institution = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smpi']
        );

        Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);

        $this->guruPiketHariIni = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $this->guruPiketHariIni->assignRole('guru_piket');

        $this->guruBiasaBukanPiket = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $this->guruBiasaBukanPiket->assignRole('guru_mapel');

        $this->kepsek = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $this->kepsek->assignRole('kepala_sekolah');

        // Daftarkan piket hari ini untuk guruPiketHariIni
        $hariIni = Schedule::getIndonesianDayName(Carbon::today());
        DutySchedule::create([
            'hari' => $hariIni,
            'teacher_id' => $this->guruPiketHariIni->id,
            'institution_id' => $this->institution->id,
            'status' => 'aktif',
        ]);

        $this->classroom = Classroom::create([
            'name' => 'Kelas 7A',
            'tingkat' => 7,
            'institution_id' => $this->institution->id,
        ]);

        $this->studentFingerprint = Student::create([
            'name' => 'Siswa Fingerprint 7A',
            'nis' => '7001',
            'fingerspot_pin' => '7001',
            'jenis_kelamin' => 'L',
            'classroom_id' => $this->classroom->id,
            'institution_id' => $this->institution->id,
            'character_score' => 100,
            'status' => 'aktif',
        ]);

        $this->studentManual = Student::create([
            'name' => 'Siswa Mati Lampu 7A',
            'nis' => '7002',
            'fingerspot_pin' => '7002',
            'jenis_kelamin' => 'P',
            'classroom_id' => $this->classroom->id,
            'institution_id' => $this->institution->id,
            'character_score' => 100,
            'status' => 'aktif',
        ]);
    }

    /**
     * Test: Menu "Data Presensi Pagi" (DailyAttendanceResource) telah dihapus dari registrasi navigasi sidebar.
     */
    public function test_daily_attendance_resource_navigation_is_deleted_from_sidebar(): void
    {
        $this->assertFalse(
            DailyAttendanceResource::shouldRegisterNavigation(),
            'Menu "Data Presensi Pagi" (DailyAttendanceResource) harus tidak lagi terdaftar pada navigasi sidebar'
        );
    }

    /**
     * Test: Halaman Input Presensi Manual dikelompokkan ke rumpun Tugas Guru Piket.
     */
    public function test_absensi_manual_is_grouped_under_tugas_guru_piket(): void
    {
        $this->assertEquals('Tugas Guru Piket', AbsensiManual::getNavigationGroup());
        $this->assertEquals('Input Presensi Manual Siswa', AbsensiManual::getNavigationLabel());
    }

    /**
     * Test: Akses Presensi Manual hanya untuk Guru Piket hari ini atau Waka/Kepsek.
     */
    public function test_absensi_manual_access_restrictions(): void
    {
        // Guru yang piket hari ini -> BISA akses
        $this->actingAs($this->guruPiketHariIni);
        $this->assertTrue(AbsensiManual::canAccess());
        $this->assertTrue(AbsensiManual::shouldRegisterNavigation());

        // Guru biasa yang BUKAN piket hari ini -> TIDAK BISA akses
        $this->actingAs($this->guruBiasaBukanPiket);
        $this->assertFalse(AbsensiManual::canAccess());
        $this->assertFalse(AbsensiManual::shouldRegisterNavigation());

        // Kepala Sekolah -> BISA akses
        $this->actingAs($this->kepsek);
        $this->assertTrue(AbsensiManual::canAccess());
    }

    /**
     * Test: Integrasi mulus antara Fingerprint dan Input Manual Guru Piket saat mati lampu.
     */
    public function test_seamless_integration_between_fingerprint_and_manual_attendance(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Siswa 1 telah absen pagi via Fingerprint pada jam 06:50
        DailyAttendance::create([
            'student_id'       => $this->studentFingerprint->id,
            'institution_id'   => $this->institution->id,
            'teacher_id'       => 1,
            'tanggal'          => $today,
            'tipe_absen'       => 'masuk',
            'metode_absen'     => 'fingerprint',
            'waktu_kedatangan' => '06:50:00',
            'status'           => 'hadir',
            'is_late'          => false,
            'catatan'          => 'Tepat Waktu via Fingerspot',
        ]);

        $this->actingAs($this->guruPiketHariIni);

        // 2. Mati lampu terjadi pada jam 07:05, Guru Piket membuka AbsensiManual
        $component = Livewire::test(AbsensiManual::class)
            ->set('selectedClassroomId', $this->classroom->id)
            ->set('selectedDate', $today)
            ->set('tipeAbsen', 'masuk');

        // Status siswa yang sudah fingerprint otomatis hadir
        $component->assertSet("attendanceData.{$this->studentFingerprint->id}", 'hadir');

        // Metadata fingerprint terbaca
        $meta = $component->get('attendanceMeta');
        $this->assertEquals('fingerprint', $meta[$this->studentFingerprint->id]['metode']);
        $this->assertEquals('06:50', $meta[$this->studentFingerprint->id]['jam']);

        // Siswa 2 yang datang saat mati lampu diinputkan hadir secara manual oleh guru piket
        $component->set("attendanceData.{$this->studentManual->id}", 'hadir');
        $component->call('simpan');

        // 3. Verifikasi di database:
        // Siswa 1 tetap berstatus metode_absen = 'fingerprint' dan jam kedatangan asli 06:50:00 TIDAK tertimpa
        $recFingerprint = DailyAttendance::where('student_id', $this->studentFingerprint->id)
            ->where('tanggal', $today)
            ->where('tipe_absen', 'masuk')
            ->first();
        $this->assertNotNull($recFingerprint);
        $this->assertEquals('fingerprint', $recFingerprint->metode_absen);
        $this->assertEquals('06:50:00', $recFingerprint->waktu_kedatangan);

        // Siswa 2 tercatat berstatus metode_absen = 'manual' dan keterangan mencatat guru piket
        $recManual = DailyAttendance::where('student_id', $this->studentManual->id)
            ->where('tanggal', $today)
            ->where('tipe_absen', 'masuk')
            ->first();
        $this->assertNotNull($recManual);
        $this->assertEquals('manual', $recManual->metode_absen);
        $this->assertEquals('hadir', $recManual->status);
        $this->assertStringContainsString('Input manual Guru Piket', $recManual->keterangan);

        // 4. Verifikasi Dasbor Guru Piket menghitung keduanya secara terintegrasi
        $dashboard = Livewire::test(PiketDashboard::class);
        $this->assertEquals(2, $dashboard->get('siswaHadir'));
    }
}
