<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\FingerspotAttendanceController;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FingerspotAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan institusi ID 1 (Yayasan) dan ID 2 (SMP)
        $inst1 = new Institution(['name' => 'Yayasan LPI Baitul Makmur', 'slug' => 'yayasan']);
        $inst1->id = 1;
        $inst1->save();

        $inst2 = new Institution(['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smpi']);
        $inst2->id = 2;
        $inst2->save();

        $this->institution = $inst2;

        $this->user = User::create([
            'name'           => 'System Admin',
            'email'          => 'admin@smpi.test',
            'password'       => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
    }

    public function test_fingerspot_ping_endpoint()
    {
        $response = $this->get('/api/fingerspot/ping');

        $response->assertStatus(200);
        $response->assertJson([
            'status'        => 'online',
            'device_target' => 'Fingerspot Revo W230N',
        ]);
    }

    public function test_fingerspot_adms_handshake()
    {
        $response = $this->get('/iclock/cdata?SN=TEST_SN_123');

        $response->assertStatus(200);
        $this->assertStringContainsString('GET OPTION FROM: TEST_SN_123', $response->getContent());
    }

    public function test_fingerspot_adms_getrequest()
    {
        $response = $this->get('/iclock/getrequest?SN=TEST_SN_123');

        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_fingerspot_adms_devicecmd()
    {
        $response = $this->post('/iclock/devicecmd?SN=TEST_SN_123');

        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_fingerspot_empty_payload()
    {
        $response = $this->postJson('/api/fingerspot/attendance', []);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ignored',
        ]);
    }

    public function test_fingerspot_webhook_records_attendance_on_time()
    {
        $classroom = Classroom::create([
            'name'           => 'VII-A',
            'tingkat'        => 7,
            'institution_id' => $this->institution->id,
            'wali_kelas_id'  => $this->user->id,
        ]);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id'   => $classroom->id,
            'name'           => 'Ahmad Santoso',
            'nis'            => '10001',
            'fingerspot_pin' => '10001',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2010-01-01',
        ]);

        // Masuk 06:00 - 06:50 -> Tepat Waktu
        $payload = [
            'type'     => 'attlog',
            'cloud_id' => 'REVO_W230N_001',
            'data'     => [
                [
                    'pin'         => '10001',
                    'scan_date'   => '2026-10-26 06:45:00',
                    'verify'      => 1,
                    'status_scan' => 0,
                ],
            ],
        ];

        $response = $this->postJson('/api/fingerspot/attendance', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total'   => 1,
        ]);

        $this->assertDatabaseHas('daily_attendances', [
            'student_id'   => $student->id,
            'tanggal'      => '2026-10-26',
            'tipe_absen'   => 'masuk',
            'metode_absen' => 'fingerprint',
            'status'       => 'hadir',
            'is_late'      => false,
        ]);
    }

    public function test_fingerspot_webhook_records_attendance_late()
    {
        $classroom = Classroom::create([
            'name'           => 'VII-A',
            'tingkat'        => 7,
            'institution_id' => $this->institution->id,
            'wali_kelas_id'  => $this->user->id,
        ]);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id'   => $classroom->id,
            'name'           => 'Budi Pratama',
            'nis'            => '10002',
            'fingerspot_pin' => '10002',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2010-01-01',
        ]);

        // Masuk > 06:50 (contoh 06:51:00) -> Terlambat
        $payload = [
            'type'     => 'attlog',
            'cloud_id' => 'REVO_W230N_001',
            'data'     => [
                [
                    'pin'         => '10002',
                    'scan_date'   => '2026-10-26 06:51:00',
                    'verify'      => 1,
                    'status_scan' => 0,
                ],
            ],
        ];

        $response = $this->postJson('/api/fingerspot/attendance', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('daily_attendances', [
            'student_id'   => $student->id,
            'tanggal'      => '2026-10-26',
            'tipe_absen'   => 'masuk',
            'is_late'      => true,
        ]);

        $att = DailyAttendance::where('student_id', $student->id)->first();
        $this->assertStringContainsString('Terlambat Masuk Pagi', $att->keterangan);
    }

    public function test_fingerspot_enforces_exact_cutoffs_for_all_three_sessions()
    {
        $classroom = Classroom::create([
            'name'           => 'VIII-B',
            'tingkat'        => 8,
            'institution_id' => $this->institution->id,
            'wali_kelas_id'  => $this->user->id,
        ]);

        $ontimeStudent = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id'   => $classroom->id,
            'name'           => 'Siswa Tepat Waktu',
            'nis'            => '5001',
            'id_finger'      => '501',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2011-01-01',
        ]);

        $lateStudent = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id'   => $classroom->id,
            'name'           => 'Siswa Terlambat',
            'nis'            => '5002',
            'id_finger'      => '502',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2011-01-01',
        ]);

        // Siswa Tepat Waktu di batas maksimal masing-masing sesi (06:50, 12:15, 15:15)
        $this->postJson('/api/fingerspot/attendance', [
            'type' => 'attlog',
            'data' => [
                ['pin' => '501', 'scan_date' => '2026-11-10 06:50:45', 'verify' => 1],
                ['pin' => '501', 'scan_date' => '2026-11-10 12:15:30', 'verify' => 1],
                ['pin' => '501', 'scan_date' => '2026-11-10 15:15:20', 'verify' => 1],
            ],
        ])->assertStatus(200);

        // Siswa Terlambat lewat 1 menit di masing-masing sesi (06:51, 12:16, 15:16)
        $this->postJson('/api/fingerspot/attendance', [
            'type' => 'attlog',
            'data' => [
                ['pin' => '502', 'scan_date' => '2026-11-10 06:51:00', 'verify' => 1],
                ['pin' => '502', 'scan_date' => '2026-11-10 12:16:00', 'verify' => 1],
                ['pin' => '502', 'scan_date' => '2026-11-10 15:16:00', 'verify' => 1],
            ],
        ])->assertStatus(200);

        foreach (['masuk', 'sholat_dhuhur', 'sholat_ashar'] as $tipe) {
            $this->assertDatabaseHas('daily_attendances', [
                'student_id' => $ontimeStudent->id,
                'tanggal'    => '2026-11-10',
                'tipe_absen' => $tipe,
                'is_late'    => false,
            ]);

            $this->assertDatabaseHas('daily_attendances', [
                'student_id' => $lateStudent->id,
                'tanggal'    => '2026-11-10',
                'tipe_absen' => $tipe,
                'is_late'    => true,
            ]);

            $lateRow = DailyAttendance::where('student_id', $lateStudent->id)
                ->where('tipe_absen', $tipe)
                ->first();
            $this->assertStringContainsString('Terlambat', $lateRow->keterangan);
        }
    }

    public function test_fingerspot_locks_first_scan_when_duplicate_or_out_of_order_batch()
    {
        $classroom = Classroom::create([
            'name'           => 'VII-C',
            'tingkat'        => 7,
            'institution_id' => $this->institution->id,
            'wali_kelas_id'  => $this->user->id,
        ]);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id'   => $classroom->id,
            'name'           => 'Doni Firmansyah',
            'nis'            => '10004',
            'fingerspot_pin' => '10004',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2010-01-01',
        ]);

        // Simulasi batch datang terbalik (scan terlambat 07:10 duluan baru scan awal 06:30)
        // Sistem HARUS mengurutkan kronologis agar scan PERTAMA (06:30) yang dikunci!
        $batch = [
            'type'     => 'attlog',
            'cloud_id' => 'REVO_W230N_001',
            'data'     => [
                ['pin' => '10004', 'scan_date' => '2026-10-26 07:10:00', 'verify' => 1],
                ['pin' => '10004', 'scan_date' => '2026-10-26 06:30:00', 'verify' => 1],
                ['pin' => '10004', 'scan_date' => '2026-10-26 06:45:00', 'verify' => 1],
            ],
        ];

        $res1 = $this->postJson('/api/fingerspot/attendance', $batch);
        $res1->assertStatus(200);

        // Scan berikutnya terpisah di jam 09:00 di sesi masuk harus tetap ditolak / terkunci
        $res2 = $this->postJson('/api/fingerspot/attendance', [
            'type' => 'attlog',
            'data' => [
                ['pin' => '10004', 'scan_date' => '2026-10-26 09:00:00', 'verify' => 1],
            ],
        ]);
        $res2->assertStatus(200);
        $res2->assertJson([
            'results' => [
                [
                    'status' => 'duplicate',
                    'locked' => true,
                ],
            ],
        ]);

        // Pastikan hanya 1 record tersimpan dan yang dikunci adalah scan pertama (06:30:00, tidak terlambat)
        $this->assertEquals(1, DailyAttendance::where('student_id', $student->id)->count());
        $this->assertDatabaseHas('daily_attendances', [
            'student_id'       => $student->id,
            'tanggal'          => '2026-10-26',
            'tipe_absen'       => 'masuk',
            'waktu_kedatangan' => '06:30:00',
            'is_late'          => false,
        ]);
    }

    public function test_fingerspot_adms_cdata_push()
    {
        $classroom = Classroom::create([
            'name'           => 'VII-B',
            'tingkat'        => 7,
            'institution_id' => $this->institution->id,
            'wali_kelas_id'  => $this->user->id,
        ]);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id'   => $classroom->id,
            'name'           => 'Citra Kirana',
            'nis'            => '10003',
            'fingerspot_pin' => '10003',
            'jenis_kelamin'  => 'P',
            'tanggal_lahir'  => '2010-01-01',
        ]);

        // Kirim raw ATTLOG push tab-separated format (jam 06:40 -> tepat waktu)
        $body = "10003\t2026-10-26 06:40:00\t0\t1\t0\t0\t0\n";

        $response = $this->call(
            'POST',
            '/iclock/cdata?SN=REVO_SN_999&table=ATTLOG',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $body
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('OK: 1', $response->getContent());

        $this->assertDatabaseHas('daily_attendances', [
            'student_id'   => $student->id,
            'tanggal'      => '2026-10-26',
            'tipe_absen'   => 'masuk',
            'metode_absen' => 'fingerprint',
            'is_late'      => false,
        ]);
    }

    public function test_fingerspot_flat_webhook_and_root_post_fallback()
    {
        $classroom = Classroom::create([
            'name'           => 'IX-A',
            'tingkat'        => 9,
            'institution_id' => $this->institution->id,
            'wali_kelas_id'  => $this->user->id,
        ]);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id'   => $classroom->id,
            'name'           => 'Abdurrohman Faqih',
            'nis'            => '0285',
            'id_finger'      => '1',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2011-05-05',
        ]);

        // Format webhook flat tanpa wrapper 'data' ke root POST /
        $flatPayload = [
            'type'      => 'attlog',
            'cloud_id'  => 'C2630450C31999',
            'pin'       => '1',
            'scan_date' => '2026-11-10 06:48:00',
            'verify'    => '1',
            'status'    => '0',
        ];

        $response = $this->postJson('/', $flatPayload);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total'   => 1,
        ]);

        $this->assertDatabaseHas('daily_attendances', [
            'student_id' => $student->id,
            'tanggal'    => '2026-11-10',
            'tipe_absen' => 'masuk',
            'is_late'    => false,
        ]);
    }

    public function test_fingerspot_pin_does_not_collide_with_unrelated_primary_id()
    {
        $classroom = Classroom::create([
            'name'           => 'VII-D',
            'tingkat'        => 7,
            'institution_id' => $this->institution->id,
            'wali_kelas_id'  => $this->user->id,
        ]);

        // Student ID 1 tidak punya ID Finger
        $otherStudent = new Student([
            'institution_id' => $this->institution->id,
            'classroom_id'   => $classroom->id,
            'name'           => 'Bukan Pemilik PIN 1',
            'nis'            => '9991',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2011-01-01',
        ]);
        $otherStudent->id = 1;
        $otherStudent->save();

        // Student ID 25 memiliki id_finger = '1'
        $ownerStudent = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id'   => $classroom->id,
            'name'           => 'Pemilik Sah PIN 1',
            'nis'            => '0285',
            'id_finger'      => '1',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2011-02-02',
        ]);

        $this->postJson('/api/fingerspot/attendance', [
            'type' => 'attlog',
            'data' => [
                ['pin' => '1', 'scan_date' => '2026-11-10 06:40:00', 'verify' => 1],
            ],
        ])->assertStatus(200);

        $this->assertEquals(0, DailyAttendance::where('student_id', $otherStudent->id)->count());
        $this->assertEquals(1, DailyAttendance::where('student_id', $ownerStudent->id)->count());
    }

    public function test_student_updating_fingerspot_pin_syncs_id_finger_properly()
    {
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'name'           => 'Test Update PIN',
            'nis'            => '8801',
            'id_finger'      => '100',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2011-01-01',
        ]);

        // Simulasi perubahan kolom TextInputColumn('fingerspot_pin') dari UI Data Siswa
        $student->fingerspot_pin = '78';
        $student->save();
        $student->refresh();

        $this->assertEquals('78', $student->id_finger);
        $this->assertEquals('78', $student->fingerspot_pin);
    }

    public function test_fingerspot_api_key_authentication()
    {
        config(['services.fingerspot.api_key' => 'secret_test_token_123']);

        $payload = [
            'type'     => 'attlog',
            'cloud_id' => 'REVO_W230N_001',
            'data'     => [],
        ];

        // 1. With wrong API key -> 401
        $resWrongKey = $this->withHeaders([
            'X-API-KEY' => 'wrong_token',
        ])->postJson('/api/fingerspot/attendance', $payload);
        $resWrongKey->assertStatus(401);

        // 2. With correct API key -> 200
        $resValid = $this->withHeaders([
            'X-API-KEY' => 'secret_test_token_123',
        ])->postJson('/api/fingerspot/attendance', $payload);
        $resValid->assertStatus(200);

        // 3. Standard webhook dari developer.fingerspot.io tanpa header key -> 200
        $resWebhook = $this->postJson('/api/fingerspot/attendance', $payload);
        $resWebhook->assertStatus(200);
    }

    public function test_fingerspot_webhook_records_attendance_using_nisn()
    {
        $classroom = Classroom::create([
            'name'           => 'VIII-A',
            'tingkat'        => 8,
            'institution_id' => $this->institution->id,
            'wali_kelas_id'  => $this->user->id,
        ]);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id'   => $classroom->id,
            'name'           => 'Fathir Rahman',
            'nis'            => '10005',
            'nisn'           => '0089876543',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2010-01-01',
        ]);

        $payload = [
            'type'     => 'attlog',
            'cloud_id' => 'REVO_W230N_001',
            'data'     => [
                [
                    'pin'         => '0089876543',
                    'scan_date'   => '2026-11-10 06:40:00',
                    'verify'      => 1,
                    'status_scan' => 0,
                ],
            ],
        ];

        $response = $this->postJson('/api/fingerspot/attendance', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total'   => 1,
            'results' => [
                [
                    'status'  => 'success',
                    'student' => 'Fathir Rahman',
                    'nis'     => '10005',
                ],
            ],
        ]);

        $this->assertDatabaseHas('daily_attendances', [
            'student_id'   => $student->id,
            'tanggal'      => '2026-11-10',
            'tipe_absen'   => 'masuk',
            'metode_absen' => 'fingerprint',
            'status'       => 'hadir',
            'is_late'      => false,
        ]);
    }

    public function test_fingerspot_webhook_records_teacher_attendance()
    {
        $teacher = User::create([
            'name'           => 'Lya Ratnawati, M.Pd',
            'email'          => 'lya.test@smpi.test',
            'password'       => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'id_finger'      => '2001',
            'is_active'      => true,
        ]);

        $payload = [
            'type'     => 'attlog',
            'cloud_id' => 'REVO_W230N_001',
            'data'     => [
                [
                    'pin'         => '2001',
                    'scan_date'   => '2026-11-10 06:45:00',
                    'verify'      => 1,
                    'status_scan' => 0,
                ],
            ],
        ];

        $response = $this->postJson('/api/fingerspot/attendance', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'results' => [
                [
                    'status'  => 'success',
                    'action'  => 'teacher_attendance',
                    'teacher' => 'Lya Ratnawati, M.Pd',
                ],
            ],
        ]);

        $this->assertDatabaseHas('teacher_attendances', [
            'teacher_id' => $teacher->id,
            'tanggal'    => '2026-11-10',
            'status'     => 'masuk',
        ]);
    }

    public function test_fingerspot_cloud_api_pull_imports_and_locks_first_scan()
    {
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'name'           => 'Lana',
            'nis'            => '39909',
            'id_finger'      => '78',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2011-01-01',
        ]);

        Http::fake([
            'https://developer.fingerspot.io/api/get_device' => Http::response([
                'success' => true,
                'data'    => ['cloud_id' => 'C2630450C31TEST', 'device_name' => 'Revo W230N'],
            ], 200),
            'https://developer.fingerspot.io/api/get_attlog' => Http::response([
                'success' => true,
                'data'    => [
                    // Terbalik: scan kedua lebih dulu dalam array
                    ['pin' => '78', 'scan_date' => '2026-11-10 07:05:00', 'verify' => 1, 'status' => 0],
                    ['pin' => '78', 'scan_date' => '2026-11-10 06:45:00', 'verify' => 1, 'status' => 0],
                ],
            ], 200),
        ]);

        $controller = new FingerspotAttendanceController();
        $result = $controller->pullFromCloudApi('C2630450C31TEST', '2026-11-10', '2026-11-10');

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total_logs']);
        $this->assertEquals(1, $result['inserted']);
        $this->assertEquals(1, $result['duplicates_locked']);

        $this->assertDatabaseHas('daily_attendances', [
            'student_id'       => $student->id,
            'tanggal'          => '2026-11-10',
            'tipe_absen'       => 'masuk',
            'waktu_kedatangan' => '06:45:00',
            'is_late'          => false,
        ]);
    }
}
