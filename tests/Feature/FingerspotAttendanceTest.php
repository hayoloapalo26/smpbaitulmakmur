<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FingerspotAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name'     => 'System Admin',
            'email'    => 'admin@smpi.test',
            'password' => bcrypt('password'),
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
        $institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smpi',
        ]);

        $classroom = Classroom::create([
            'name'           => 'VII-A',
            'tingkat'        => 7,
            'institution_id' => $institution->id,
        ]);

        $student = Student::create([
            'classroom_id'   => $classroom->id,
            'name'           => 'Ahmad Santoso',
            'nis'            => '10001',
            'fingerspot_pin' => '10001',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2010-01-01',
        ]);

        $payload = [
            'type'     => 'attlog',
            'cloud_id' => 'REVO_W230N_001',
            'data'     => [
                [
                    'pin'         => '10001',
                    'scan_date'   => '2026-10-26 07:10:00',
                    'verify'      => 1, // Fingerprint
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
        $institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smpi',
        ]);

        $classroom = Classroom::create([
            'name'           => 'VII-A',
            'tingkat'        => 7,
            'institution_id' => $institution->id,
        ]);

        $student = Student::create([
            'classroom_id'   => $classroom->id,
            'name'           => 'Budi Pratama',
            'nis'            => '10002',
            'fingerspot_pin' => '10002',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2010-01-01',
        ]);

        $payload = [
            'type'     => 'attlog',
            'cloud_id' => 'REVO_W230N_001',
            'data'     => [
                [
                    'pin'         => '10002',
                    'scan_date'   => '2026-10-26 07:25:00', // Jam 07:25 > batas 07:15
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
    }

    public function test_fingerspot_adms_cdata_push()
    {
        $institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smpi',
        ]);

        $classroom = Classroom::create([
            'name'           => 'VII-B',
            'tingkat'        => 7,
            'institution_id' => $institution->id,
        ]);

        $student = Student::create([
            'classroom_id'   => $classroom->id,
            'name'           => 'Citra Kirana',
            'nis'            => '10003',
            'fingerspot_pin' => '10003',
            'jenis_kelamin'  => 'P',
            'tanggal_lahir'  => '2010-01-01',
        ]);

        // Kirim raw ATTLOG push tab-separated format
        $body = "10003\t2026-10-26 07:05:00\t0\t1\t0\t0\t0\n";

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

    public function test_fingerspot_prevents_duplicate_attendance()
    {
        $institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smpi-dup',
        ]);

        $classroom = Classroom::create([
            'name'           => 'VII-C',
            'tingkat'        => 7,
            'institution_id' => $institution->id,
        ]);

        $student = Student::create([
            'classroom_id'   => $classroom->id,
            'name'           => 'Doni Firmansyah',
            'nis'            => '10004',
            'fingerspot_pin' => '10004',
            'jenis_kelamin'  => 'L',
            'tanggal_lahir'  => '2010-01-01',
        ]);

        $payload = [
            'type'     => 'attlog',
            'cloud_id' => 'REVO_W230N_001',
            'data'     => [
                [
                    'pin'         => '10004',
                    'scan_date'   => '2026-10-26 07:00:00',
                    'verify'      => 1,
                    'status_scan' => 0,
                ],
            ],
        ];

        // First scan
        $res1 = $this->postJson('/api/fingerspot/attendance', $payload);
        $res1->assertStatus(200);

        // Second scan (duplicate)
        $res2 = $this->postJson('/api/fingerspot/attendance', $payload);
        $res2->assertStatus(200);
        $res2->assertJson([
            'results' => [
                [
                    'status' => 'duplicate',
                ],
            ],
        ]);

        // Ensure only 1 record created in database
        $this->assertEquals(1, DailyAttendance::where('student_id', $student->id)->count());
    }
}
