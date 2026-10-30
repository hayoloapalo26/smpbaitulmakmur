<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\DailyTodoList;
use App\Filament\Staff\Pages\OptionalTodoList;
use App\Filament\Staff\Resources\MandatoryTaskResource;
use App\Filament\Staff\Resources\OptionalTaskResource;
use App\Filament\Staff\Resources\OptionalTaskResource\Pages\CreateOptionalTask;
use App\Filament\Staff\Widgets\PendingTodoReminderWidget;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\OptionalTask;
use App\Models\OptionalTaskSubmission;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TugasInsidentilTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $kepalaSekolah;
    protected User $userHumas;
    protected User $userSarpras;
    protected User $userGuru;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'humas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sarpras', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->kepalaSekolah = User::create([
            'name' => 'Kepala Sekolah SMP',
            'username' => 'ks_smp',
            'email' => 'ks@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        $this->userHumas = User::create([
            'name' => 'Staf Humas',
            'username' => 'humas_smp',
            'email' => 'humas@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->userHumas->assignRole('humas');

        $this->userSarpras = User::create([
            'name' => 'Staf Sarpras',
            'username' => 'sarpras_smp',
            'email' => 'sarpras@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->userSarpras->assignRole('sarpras');

        $this->userGuru = User::create([
            'name' => 'Guru Matematika',
            'username' => 'guru_smp',
            'email' => 'guru@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->userGuru->assignRole('guru_mapel');
    }

    /**
     * Test 1: Fitur To-Do List Harian (Wajib) dinonaktifkan / disembunyikan untuk lembaga.
     */
    public function test_mandatory_todo_list_is_disabled_for_institution(): void
    {
        $this->actingAs($this->kepalaSekolah);
        $this->assertFalse(MandatoryTaskResource::canAccess());
        $this->assertFalse(MandatoryTaskResource::shouldRegisterNavigation());
        $this->assertFalse(DailyTodoList::shouldRegisterNavigation());

        $this->actingAs($this->userGuru);
        $this->assertFalse(MandatoryTaskResource::canAccess());
        $this->assertFalse(MandatoryTaskResource::shouldRegisterNavigation());
        $this->assertFalse(DailyTodoList::shouldRegisterNavigation());
    }

    /**
     * Test 2: Kepala Sekolah dapat mengakses & membuat Tugas Insidentil di OptionalTaskResource.
     */
    public function test_kepala_sekolah_can_create_tugas_insidentil(): void
    {
        $this->actingAs($this->kepalaSekolah);
        $this->assertTrue(OptionalTaskResource::canAccess());
        $this->assertTrue(OptionalTaskResource::shouldRegisterNavigation());

        Livewire::test(CreateOptionalTask::class)
            ->fillForm([
                'title' => 'Liputan Dokumentasi Milad Sekolah',
                'description' => 'Mohon dokumentasikan seluruh rangkaian acara milad.',
                'target_type' => 'roles',
                'target_roles' => ['humas'],
                'reward_points' => 10,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('optional_tasks', [
            'title' => 'Liputan Dokumentasi Milad Sekolah',
            'target_type' => 'roles',
            'reward_points' => 10,
            'created_by' => $this->kepalaSekolah->id,
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 3: Tugas Insidentil yang ditujukan ke 'all' muncul untuk seluruh warga lembaga.
     */
    public function test_tugas_insidentil_targeted_to_all_appears_for_all_staff(): void
    {
        $task = OptionalTask::create([
            'institution_id' => $this->institution->id,
            'created_by' => $this->kepalaSekolah->id,
            'title' => 'Kerja Bakti Bersama Seluruh Warga Lembaga',
            'description' => 'Semua guru dan staf berpartisipasi dalam kerja bakti.',
            'target_type' => 'all',
            'reward_points' => 5,
            'is_active' => true,
        ]);

        $this->assertTrue($task->isAssignedToUser($this->userHumas));
        $this->assertTrue($task->isAssignedToUser($this->userSarpras));
        $this->assertTrue($task->isAssignedToUser($this->userGuru));

        $this->actingAs($this->userHumas);
        Livewire::test(OptionalTodoList::class)
            ->assertSee('Kerja Bakti Bersama Seluruh Warga Lembaga')
            ->assertSee('Seluruh Lembaga');
    }

    /**
     * Test 4: Tugas Insidentil yang diarahkan ke divisi tertentu (contoh: Sarpras & Humas)
     * hanya muncul bagi staf yang memiliki role tersebut.
     */
    public function test_tugas_insidentil_targeted_to_specific_roles(): void
    {
        $taskSarpras = OptionalTask::create([
            'institution_id' => $this->institution->id,
            'created_by' => $this->kepalaSekolah->id,
            'title' => 'Inventarisasi Meja & Kursi Kelas',
            'description' => 'Cek kondisi fisik mebel kelas.',
            'target_type' => 'roles',
            'target_roles' => ['sarpras'],
            'reward_points' => 5,
            'is_active' => true,
        ]);

        $this->assertTrue($taskSarpras->isAssignedToUser($this->userSarpras));
        $this->assertFalse($taskSarpras->isAssignedToUser($this->userHumas));
        $this->assertFalse($taskSarpras->isAssignedToUser($this->userGuru));

        // Sarpras melihat tugas di halamannya
        $this->actingAs($this->userSarpras);
        Livewire::test(OptionalTodoList::class)
            ->assertSee('Inventarisasi Meja & Kursi Kelas');

        // Humas tidak melihat tugas sarpras
        $this->actingAs($this->userHumas);
        Livewire::test(OptionalTodoList::class)
            ->assertDontSee('Inventarisasi Meja & Kursi Kelas');
    }

    /**
     * Test 5: Tugas Insidentil yang diarahkan ke personil / user spesifik.
     */
    public function test_tugas_insidentil_targeted_to_specific_user(): void
    {
        $taskKhusus = OptionalTask::create([
            'institution_id' => $this->institution->id,
            'created_by' => $this->kepalaSekolah->id,
            'title' => 'Tugas Khusus Staf Humas',
            'description' => 'Pertemuan dengan media lokal.',
            'target_type' => 'users',
            'target_user_ids' => [$this->userHumas->id],
            'reward_points' => 15,
            'is_active' => true,
        ]);

        $this->assertTrue($taskKhusus->isAssignedToUser($this->userHumas));
        $this->assertFalse($taskKhusus->isAssignedToUser($this->userSarpras));

        $this->actingAs($this->userHumas);
        Livewire::test(OptionalTodoList::class)
            ->assertSee('Tugas Khusus Staf Humas')
            ->assertSee('Ditugaskan Khusus Anda');
    }

    /**
     * Test 6: Staf dapat mengirimkan laporan pelaksanaan tugas insidentil.
     */
    public function test_staff_can_submit_tugas_insidentil_report(): void
    {
        $task = OptionalTask::create([
            'institution_id' => $this->institution->id,
            'created_by' => $this->kepalaSekolah->id,
            'title' => 'Publikasi Poster PPDB di Instagram',
            'description' => 'Upload poster dan cantumkan link drive.',
            'target_type' => 'roles',
            'target_roles' => ['humas'],
            'reward_points' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($this->userHumas);

        Livewire::test(OptionalTodoList::class)
            ->callTableAction('fill_form', $task, [
                'Konfirmasi Pelaksanaan' => true,
                'Uraian Kegiatan & Hasil' => 'Poster berhasil diposting di IG dan FB sekolah.',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('optional_task_submissions', [
            'optional_task_id' => $task->id,
            'user_id' => $this->userHumas->id,
        ]);
    }
}
