<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\TeacherAttendanceResource\Pages\ManageTeacherAttendances;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeacherAttendance;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherAttendanceReportSearchTest extends TestCase
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
                'name' => "Dummy Seed User {$i}",
                'email' => "dummy_seed_{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
        }

        $this->waka = User::create([
            'name' => 'Waka Kurikulum SMP',
            'email' => 'waka@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->waka->assignRole('waka_kurikulum');

        $this->guru1 = User::create([
            'name' => 'Zaenal Musthofa, S.Pd',
            'email' => 'zaenal@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru1->assignRole('guru_mapel');

        $this->guru2 = User::create([
            'name' => 'Siti Aminah, M.Pd',
            'email' => 'siti@baitulmakmur.sch.id',
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

        $kelasB = Classroom::create([
            'name' => 'VII-B',
            'tingkat' => 'VII',
            'institution_id' => $this->institution->id,
        ]);

        $mapelMTK = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK',
            'institution_id' => $this->institution->id,
        ]);

        $mapelIPA = Subject::create([
            'name' => 'Ilmu Pengetahuan Alam',
            'kode' => 'IPA',
            'institution_id' => $this->institution->id,
        ]);

        Schedule::create([
            'classroom_id' => $kelasA->id,
            'subject_id' => $mapelMTK->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);

        Schedule::create([
            'classroom_id' => $kelasB->id,
            'subject_id' => $mapelIPA->id,
            'teacher_id' => $this->guru2->id,
            'hari' => 'Selasa',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 1: Action rekap_jam_guru tersedia dan memiliki tombol submit/cari
     */
    public function test_rekap_jam_guru_action_has_search_button_and_searchable_fields(): void
    {
        $this->actingAs($this->waka);

        $livewire = Livewire::test(ManageTeacherAttendances::class);

        $livewire->assertActionExists('rekap_jam_guru');

        $action = $livewire->instance()->getAction('rekap_jam_guru');
        $this->assertNotNull($action);

        // Pastikan modal submit action aktif dan bertuliskan Cari / Tampilkan Data
        $submitAction = $action->getModalSubmitAction();
        $this->assertNotNull($submitAction);
        $this->assertEquals('Cari / Tampilkan Data', $submitAction->getLabel());
    }

    /**
     * Test 2: Blade view laporan rekapitulasi presensi guru ter-render dengan fitur search bar interaktif
     */
    public function test_teacher_attendance_report_view_renders_with_search_bar(): void
    {
        $this->actingAs($this->waka);

        $view = $this->view('filament.staff.modals.teacher-attendance-report', [
            'selectedMonth' => 10,
            'selectedYear' => 2026,
            'selectedTeacherId' => null,
        ]);

        $view->assertSee('Laporan Rekapitulasi Beban & Kehadiran Jam Guru', false);
        $view->assertSee('Ketik nama guru atau NIP untuk mencari');
        $view->assertSee('Cari');
        $view->assertSee('Semua (');
        $view->assertSee('Jam Kurang');
        $view->assertSee('Tuntas (≥100%)', false);
        $view->assertSee('Reset');
    }

    /**
     * Test 3: Filter guru spesifik membatasi data hanya untuk guru tersebut
     */
    public function test_teacher_attendance_report_view_filters_by_specific_teacher(): void
    {
        $this->actingAs($this->waka);

        // Buat view dengan selectedTeacherId Guru 1
        $view = $this->view('filament.staff.modals.teacher-attendance-report', [
            'selectedMonth' => 10,
            'selectedYear' => 2026,
            'selectedTeacherId' => $this->guru1->id,
        ]);

        $view->assertSee($this->guru1->name);
        $view->assertDontSee($this->guru2->name);
    }
}
