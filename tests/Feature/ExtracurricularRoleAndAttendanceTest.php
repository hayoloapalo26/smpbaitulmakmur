<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\ExtracurricularAttendanceResource;
use App\Filament\Staff\Resources\ExtracurricularResource;
use App\Models\Classroom;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExtracurricularRoleAndAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $wakaKesiswaan;
    protected User $guruEskul;
    protected User $guruMapel;
    protected User $superadmin;
    protected Student $student1;
    protected Student $student2;
    protected Classroom $class7A;
    protected Extracurricular $eskulPramuka;
    protected Extracurricular $eskulFutsal;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_ekstrakurikuler', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        Institution::firstOrCreate(['id' => 1], ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']);
        $this->institution = Institution::firstOrCreate(['id' => 2], ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-bm']);

        $this->wakaKesiswaan = User::create([
            'name' => 'Ustadz Waka Kesiswaan',
            'username' => 'waka_kesiswaan',
            'email' => 'kesiswaan@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->wakaKesiswaan->assignRole('waka_kesiswaan');

        $this->guruEskul = User::create([
            'name' => 'Coach Pembina Eskul',
            'username' => 'guru_eskul',
            'email' => 'eskul@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruEskul->assignRole('guru_ekstrakurikuler');

        $this->guruMapel = User::create([
            'name' => 'Guru Bahasa',
            'username' => 'guru_mapel',
            'email' => 'mapel@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruMapel->assignRole('guru_mapel');

        $this->superadmin = User::create([
            'name' => 'Superadmin',
            'username' => 'superadmin',
            'email' => 'superadmin@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->superadmin->assignRole('superadmin');

        $this->class7A = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);

        $this->student1 = Student::create([
            'name' => 'Ahmad Futsal',
            'nis' => '7001',
            'classroom_id' => $this->class7A->id,
            'institution_id' => $this->institution->id,
            'tanggal_lahir' => '2012-04-10',
            'jenis_kelamin' => 'L',
        ]);

        $this->student2 = Student::create([
            'name' => 'Budi Pramuka',
            'nis' => '7002',
            'classroom_id' => $this->class7A->id,
            'institution_id' => $this->institution->id,
            'tanggal_lahir' => '2012-06-15',
            'jenis_kelamin' => 'L',
        ]);

        $this->eskulPramuka = Extracurricular::create([
            'name' => 'Pramuka',
            'hari' => 'Jumat',
            'jam_mulai' => '15:00',
            'jam_selesai' => '17:00',
            'lokasi' => 'Lapangan Utama',
            'pembina_id' => $this->guruEskul->id,
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);

        $this->eskulFutsal = Extracurricular::create([
            'name' => 'Futsal',
            'hari' => 'Kamis',
            'jam_mulai' => '15:30',
            'jam_selesai' => '17:00',
            'lokasi' => 'Lapangan Olahraga',
            'pembina_id' => $this->guruEskul->id,
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: User model role methods and display name
     */
    public function test_guru_ekstrakurikuler_role_methods(): void
    {
        $this->assertTrue($this->guruEskul->isGuruEkstrakurikuler());
        $this->assertFalse($this->wakaKesiswaan->isGuruEkstrakurikuler());
        $this->assertFalse($this->guruMapel->isGuruEkstrakurikuler());

        $this->assertEquals('🏸 Guru Ekstrakurikuler', User::getRoleDisplayName('guru_ekstrakurikuler'));
    }

    /**
     * Test 2: Extracurricular schedule creation and customization by Waka Kesiswaan
     */
    public function test_waka_kesiswaan_can_manage_extracurricular_schedules(): void
    {
        $this->actingAs($this->wakaKesiswaan);
        $this->assertTrue(ExtracurricularResource::canAccess());
        $this->assertTrue(ExtracurricularResource::shouldRegisterNavigation());
        $this->assertTrue(Gate::allows('create', Extracurricular::class));

        // Create a new extracurricular dynamically
        $newEskul = Extracurricular::create([
            'name' => 'Robotik & Coding',
            'hari' => 'Sabtu',
            'jam_mulai' => '09:00',
            'jam_selesai' => '11:00',
            'lokasi' => 'Lab Komputer',
            'pembina_id' => $this->guruEskul->id,
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('extracurriculars', [
            'name' => 'Robotik & Coding',
            'hari' => 'Sabtu',
            'jam_mulai' => '09:00',
        ]);

        // Guru Eskul CANNOT create or edit extracurricular master schedules
        $this->actingAs($this->guruEskul);
        $this->assertFalse(ExtracurricularResource::canAccess());
        $this->assertFalse(Gate::allows('create', Extracurricular::class));
    }

    /**
     * Test 3: Guru Ekstrakurikuler can record attendance per extracurricular
     */
    public function test_guru_ekstrakurikuler_can_record_attendance(): void
    {
        $this->actingAs($this->guruEskul);
        $this->assertTrue(ExtracurricularAttendanceResource::canAccess());
        $this->assertTrue(ExtracurricularAttendanceResource::shouldRegisterNavigation());

        // Navigation group for guru eskul is 'Tugas Guru Ekstrakurikuler'
        $this->assertEquals('Tugas Guru Ekstrakurikuler', ExtracurricularAttendanceResource::getNavigationGroup());

        // Record attendance
        $att = ExtracurricularAttendance::create([
            'extracurricular_id' => $this->eskulFutsal->id,
            'student_id' => $this->student1->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'catatan' => 'Latihan dribbling dan shooting',
            'pembina_id' => $this->guruEskul->id,
            'institution_id' => $this->institution->id,
        ]);

        $this->assertDatabaseHas('extracurricular_attendances', [
            'extracurricular_id' => $this->eskulFutsal->id,
            'student_id' => $this->student1->id,
            'status' => 'hadir',
        ]);
    }

    /**
     * Test 4: Waka Kesiswaan sees attendance recap under Kesiswaan & Karakter
     */
    public function test_waka_kesiswaan_sees_attendance_recap(): void
    {
        $this->actingAs($this->wakaKesiswaan);
        $this->assertTrue(ExtracurricularAttendanceResource::canAccess());

        // Navigation group for waka kesiswaan is 'Kesiswaan & Karakter'
        $this->assertEquals('Kesiswaan & Karakter', ExtracurricularAttendanceResource::getNavigationGroup());
        $this->assertEquals('Rekap Presensi Ekstrakurikuler', ExtracurricularAttendanceResource::getNavigationLabel());

        // Query shows all attendances
        ExtracurricularAttendance::create([
            'extracurricular_id' => $this->eskulPramuka->id,
            'student_id' => $this->student2->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'pembina_id' => $this->guruEskul->id,
            'institution_id' => $this->institution->id,
        ]);

        $allCount = ExtracurricularAttendanceResource::getEloquentQuery()->count();
        $this->assertGreaterThanOrEqual(1, $allCount);
    }

    /**
     * Test 5: Extracurricular Attendance PDF report export
     */
    public function test_extracurricular_attendance_pdf_report(): void
    {
        ExtracurricularAttendance::create([
            'extracurricular_id' => $this->eskulFutsal->id,
            'student_id' => $this->student1->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'pembina_id' => $this->guruEskul->id,
            'institution_id' => $this->institution->id,
        ]);

        // Waka Kesiswaan can download PDF
        $responseWaka = $this->actingAs($this->wakaKesiswaan)->get(route('report.extracurricular-attendance', [
            'extracurricular_id' => $this->eskulFutsal->id,
        ]));
        $responseWaka->assertStatus(200);

        // Guru Eskul can download PDF
        $responseEskul = $this->actingAs($this->guruEskul)->get(route('report.extracurricular-attendance', [
            'extracurricular_id' => $this->eskulFutsal->id,
        ]));
        $responseEskul->assertStatus(200);

        // Guru Mapel without authorization is forbidden
        $responseMapel = $this->actingAs($this->guruMapel)->get(route('report.extracurricular-attendance'));
        $responseMapel->assertStatus(403);
    }
}
