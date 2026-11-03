<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeacherAttendance;
use App\Models\User;
use App\Services\TeacherAttendanceReportService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherAttendancePdfExportTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $waka;
    protected User $guru1;
    protected User $guru2;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->institution = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        // Buat dummy user agar auto-increment ID melewati list pengecualian ([1, 2, 3, 25, 30, 31, 32])
        for ($i = 0; $i < 35; $i++) {
            User::create([
                'name' => "Dummy User {$i}",
                'email' => "dummy_usr_{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
        }

        $this->waka = User::create([
            'name' => 'Waka Kurikulum SMP',
            'email' => 'waka_kur@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->waka->assignRole('waka_kurikulum');

        $this->guru1 = User::create([
            'name' => 'Ahmad Dahlan, S.Pd',
            'nuptk' => '1234567890123456',
            'email' => 'dahlan@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru1->assignRole('guru_mapel');

        $this->guru2 = User::create([
            'name' => 'Dewi Sartika, M.Pd',
            'nuptk' => '6543210987654321',
            'email' => 'sartika@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru2->assignRole('guru_mapel');

        $kelasA = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => 'VII',
            'institution_id' => $this->institution->id,
        ]);

        $mapelIPA = Subject::create([
            'name' => 'Ilmu Pengetahuan Alam',
            'kode' => 'IPA',
            'institution_id' => $this->institution->id,
        ]);

        // Buat jadwal untuk guru1 di hari Senin & Selasa
        Schedule::create([
            'teacher_id' => $this->guru1->id,
            'classroom_id' => $kelasA->id,
            'subject_id' => $mapelIPA->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);
        Schedule::create([
            'teacher_id' => $this->guru1->id,
            'classroom_id' => $kelasA->id,
            'subject_id' => $mapelIPA->id,
            'hari' => 'Selasa',
            'jam_ke' => 2,
            'institution_id' => $this->institution->id,
        ]);

        // Catat presensi hadir untuk guru1
        TeacherAttendance::create([
            'teacher_id' => $this->guru1->id,
            'classroom_id' => $kelasA->id,
            'subject_id' => $mapelIPA->id,
            'tanggal' => '2026-10-05',
            'status' => 'hadir',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 1: Service TeacherAttendanceReportService menghasilkan data yang akurat
     */
    public function test_teacher_attendance_report_service_calculates_correctly(): void
    {
        $data = TeacherAttendanceReportService::getReportData(10, 2026, null);

        $this->assertIsArray($data);
        $this->assertEquals(10, $data['month']);
        $this->assertEquals(2026, $data['year']);
        $this->assertNotEmpty($data['reportData']);
        $this->assertArrayHasKey('totals', $data);

        // Verifikasi data spesifik guru 1
        $guru1Data = collect($data['reportData'])->firstWhere('teacher_id', $this->guru1->id);
        $this->assertNotNull($guru1Data);
        $this->assertEquals($this->guru1->name, $guru1Data['name']);
        $this->assertEquals(2, $guru1Data['weekly_jp']);
        $this->assertGreaterThan(0, $guru1Data['monthly_target_jp']);
        $this->assertEquals(1, $guru1Data['hadir_jp']);
    }

    /**
     * Test 2: Route PDF /print/teacher-attendance-rekap menghasilkan file PDF utuh dengan status 200
     */
    public function test_teacher_attendance_pdf_route_returns_pdf(): void
    {
        $this->actingAs($this->waka);

        $response = $this->get(route('academic.teacher-attendance.rekap.pdf', [
            'bulan' => 10,
            'tahun' => 2026,
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('rekap-presensi-beban-jam-guru-oktober-2026.pdf', $response->headers->get('Content-Disposition') ?? '');
    }

    /**
     * Test 3: Route PDF dengan filter guru spesifik hanya memuat guru terpilih
     */
    public function test_teacher_attendance_pdf_route_with_teacher_filter(): void
    {
        $this->actingAs($this->waka);

        $response = $this->get(route('academic.teacher-attendance.rekap.pdf', [
            'bulan' => 10,
            'tahun' => 2026,
            'teacher_id' => $this->guru1->id,
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 4: View modal memuat link tombol 'Unduh / Cetak PDF Resmi' dan tombol 'Print Langsung'
     */
    public function test_teacher_attendance_modal_view_contains_pdf_and_print_buttons(): void
    {
        $this->actingAs($this->waka);

        $view = $this->view('filament.staff.modals.teacher-attendance-report', [
            'selectedMonth' => 10,
            'selectedYear' => 2026,
            'selectedTeacherId' => null,
        ]);

        $view->assertSee('Unduh / Cetak PDF Resmi');
        $view->assertSee('print/teacher-attendance-rekap?bulan=10');
        $view->assertSee('Print Langsung');
        $view->assertSee('@media print', false);
        $view->assertSee('attendance-report-printable');
    }
}
