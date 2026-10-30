<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\AbsensiManual;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Institution;
use App\Models\PrayerAttendance;
use App\Models\Student;
use App\Models\StudentHalangan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceFingerprintAndManualIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected Classroom $classroom;
    protected Student $student1;
    protected Student $student2;
    protected Student $studentFemale;
    protected User $guruPiket;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smp-islam-baitul-makmur',
        ]);

        $this->classroom = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => 7,
            'institution_id' => $this->institution->id,
        ]);

        $this->student1 = Student::create([
            'name' => 'Muhammad Ali',
            'nis' => '1001',
            'fingerspot_pin' => '1001',
            'classroom_id' => $this->classroom->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-01-01',
            'institution_id' => $this->institution->id,
        ]);

        $this->student2 = Student::create([
            'name' => 'Budi Pratama',
            'nis' => '1002',
            'fingerspot_pin' => '1002',
            'classroom_id' => $this->classroom->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-02-02',
            'institution_id' => $this->institution->id,
        ]);

        $this->studentFemale = Student::create([
            'name' => 'Aisyah Putri',
            'nis' => '1003',
            'fingerspot_pin' => '1003',
            'classroom_id' => $this->classroom->id,
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2012-03-03',
            'institution_id' => $this->institution->id,
        ]);

        $this->guruPiket = User::create([
            'name' => 'Ustadz Piket',
            'email' => 'piket@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruPiket->assignRole('guru_piket');

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));
    }

    /**
     * 1. Test verifikasi bahwa fitur scan barcode lama benar-benar telah dihapus.
     */
    public function test_barcode_scanner_page_is_deleted_and_route_does_not_exist(): void
    {
        $this->assertFalse(
            class_exists('App\Filament\Staff\Pages\ScannerPresensi'),
            'Class ScannerPresensi harus sudah dihapus dari codebase'
        );

        $this->assertFalse(
            Route::has('filament.staff.pages.scanner-presensi'),
            'Route filament.staff.pages.scanner-presensi tidak boleh lagi terdaftar'
        );
    }

    /**
     * 2. Test absensi sidik jari (Fingerprint) masuk pagi via webhook Fingerspot.
     */
    public function test_fingerspot_morning_fingerprint_records_attendance(): void
    {
        // Scan jam 07:05 (Tepat waktu)
        $payload = [
            'type' => 'attlog',
            'data' => [
                [
                    'pin' => '1001',
                    'scan_date' => '2026-11-02 07:05:00',
                    'verify' => 1, // Fingerprint
                    'status_scan' => 0,
                ],
                [
                    'pin' => '1002',
                    'scan_date' => '2026-11-02 07:25:00', // Terlambat (> 07:15)
                    'verify' => 1, // Fingerprint
                    'status_scan' => 0,
                ],
            ],
        ];

        $response = $this->postJson('/api/fingerspot/attendance', $payload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verifikasi Siswa 1 tercatat Hadir tepat waktu dengan metode fingerprint
        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->student1->id,
            'tanggal' => '2026-11-02',
            'tipe_absen' => 'masuk',
            'metode_absen' => 'fingerprint',
            'status' => 'hadir',
            'is_late' => 0,
        ]);

        // Verifikasi Siswa 2 tercatat Hadir terlambat dengan metode fingerprint
        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->student2->id,
            'tanggal' => '2026-11-02',
            'tipe_absen' => 'masuk',
            'metode_absen' => 'fingerprint',
            'status' => 'hadir',
            'is_late' => 1,
        ]);
    }

    /**
     * 3. Test absensi fingerprint saat sholat dhuhur otomatis sinkron ke tabel PrayerAttendance.
     */
    public function test_fingerspot_fingerprint_prayer_syncs_to_prayer_attendance(): void
    {
        // Scan jam 12:20 (Waktu Sholat Dhuhur)
        $payload = [
            'type' => 'attlog',
            'data' => [
                [
                    'pin' => '1001',
                    'scan_date' => '2026-11-02 12:20:00',
                    'verify' => 1,
                    'status_scan' => 0,
                ],
            ],
        ];

        $response = $this->postJson('/api/fingerspot/attendance', $payload);
        $response->assertStatus(200);

        // Daily attendance tercatat
        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->student1->id,
            'tanggal' => '2026-11-02',
            'tipe_absen' => 'sholat_dhuhur',
            'metode_absen' => 'fingerprint',
            'status' => 'hadir',
        ]);

        // Otomatis sinkron ke PrayerAttendance (Dhuhur)
        $this->assertDatabaseHas('prayer_attendances', [
            'student_id' => $this->student1->id,
            'tanggal' => '2026-11-02',
            'jenis_sholat' => 'Dhuhur',
            'status' => 'hadir',
        ]);
    }

    /**
     * 4. Test absensi fingerprint via Direct ADMS / IClock (protokol langsung mesin ke server).
     */
    public function test_fingerspot_adms_direct_push_protocol(): void
    {
        $rawAttlog = "1001\t2026-11-02 07:08:00\t0\t1\n";

        $response = $this->call(
            'POST',
            '/iclock/cdata?SN=REVO_W230N_TEST&table=ATTLOG',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $rawAttlog
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('OK: 1', $response->getContent());

        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->student1->id,
            'tanggal' => '2026-11-02',
            'metode_absen' => 'fingerprint',
            'status' => 'hadir',
        ]);
    }

    /**
     * 5. Test fitur Absensi Manual kolektif per kelas oleh guru/piket.
     */
    public function test_manual_attendance_page_saves_attendance_data(): void
    {
        $this->actingAs($this->guruPiket);

        // Isi presensi manual untuk siswa di kelas VII-A
        Livewire::test(AbsensiManual::class)
            ->set('selectedClassroomId', $this->classroom->id)
            ->set('selectedDate', '2026-11-02')
            ->set('tipeAbsen', 'masuk')
            ->set('attendanceData', [
                $this->student1->id => 'hadir',
                $this->student2->id => 'izin',
                $this->studentFemale->id => 'sakit',
            ])
            ->call('simpan');

        // Pastikan tersimpan dengan metode_absen = 'manual'
        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->student1->id,
            'tanggal' => '2026-11-02',
            'tipe_absen' => 'masuk',
            'metode_absen' => 'manual',
            'status' => 'hadir',
        ]);

        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->student2->id,
            'tanggal' => '2026-11-02',
            'tipe_absen' => 'masuk',
            'metode_absen' => 'manual',
            'status' => 'izin',
        ]);

        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->studentFemale->id,
            'tanggal' => '2026-11-02',
            'tipe_absen' => 'masuk',
            'metode_absen' => 'manual',
            'status' => 'sakit',
        ]);
    }

    /**
     * 6. Test fitur Absensi Manual untuk sholat otomatis sinkron ke PrayerAttendance.
     */
    public function test_manual_prayer_attendance_syncs_with_prayer_attendance_table(): void
    {
        $this->actingAs($this->guruPiket);

        Livewire::test(AbsensiManual::class)
            ->set('selectedClassroomId', $this->classroom->id)
            ->set('selectedDate', '2026-11-02')
            ->set('tipeAbsen', 'sholat_ashar')
            ->set('attendanceData', [
                $this->student1->id => 'hadir',
                $this->student2->id => 'alpa',
            ])
            ->call('simpan');

        // Verifikasi tersimpan di daily_attendances
        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->student1->id,
            'tanggal' => '2026-11-02',
            'tipe_absen' => 'sholat_ashar',
            'metode_absen' => 'manual',
            'status' => 'hadir',
        ]);

        // Verifikasi tersinkron otomatis ke PrayerAttendance
        $this->assertDatabaseHas('prayer_attendances', [
            'student_id' => $this->student1->id,
            'tanggal' => '2026-11-02',
            'jenis_sholat' => 'Ashar',
            'status' => 'hadir',
        ]);

        $this->assertDatabaseHas('prayer_attendances', [
            'student_id' => $this->student2->id,
            'tanggal' => '2026-11-02',
            'jenis_sholat' => 'Ashar',
            'status' => 'alpa',
        ]);
    }

    /**
     * 7. Test siswi yang tercatat halangan otomatis diatur statusnya pada absensi manual sholat.
     */
    public function test_female_student_halangan_is_recognized_in_manual_prayer_attendance(): void
    {
        // Daftarkan siswi sedang halangan pada 2026-11-02
        StudentHalangan::create([
            'student_id' => $this->studentFemale->id,
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-05',
            'recorded_by' => $this->guruPiket->id,
        ]);

        $this->actingAs($this->guruPiket);

        $component = Livewire::test(AbsensiManual::class)
            ->set('selectedClassroomId', $this->classroom->id)
            ->set('selectedDate', '2026-11-02')
            ->set('tipeAbsen', 'sholat_dhuhur');

        // Status default siswi halangan harus otomatis 'halangan'
        $component->assertSet("attendanceData.{$this->studentFemale->id}", 'halangan');

        $component->call('simpan');

        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $this->studentFemale->id,
            'tanggal' => '2026-11-02',
            'tipe_absen' => 'sholat_dhuhur',
            'status' => 'halangan',
        ]);
    }
}
