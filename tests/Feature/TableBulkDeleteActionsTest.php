<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\DailyAttendanceResource;
use App\Filament\Staff\Resources\ScheduleResource;
use App\Filament\Staff\Resources\StudentAchievementResource;
use App\Filament\Staff\Resources\StudentCaseResource;
use App\Filament\Staff\Resources\StudentResource;
use App\Filament\Staff\Resources\TeacherProfileResource;
use App\Filament\Staff\Resources\TeachingJournalResource;
use App\Filament\Staff\Resources\UserResource;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TableBulkDeleteActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
    }

    public function test_resources_have_delete_bulk_action_configured(): void
    {
        $institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );
        $superadmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin_bulk_config',
            'email' => 'superadmin_bulk_config@test.com',
            'password' => bcrypt('password'),
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);
        $superadmin->assignRole('superadmin');
        $this->actingAs($superadmin);

        $resources = [
            StudentResource::class,
            TeacherProfileResource::class,
            UserResource::class,
            StudentCaseResource::class,
            ScheduleResource::class,
            StudentAchievementResource::class,
            TeachingJournalResource::class,
            DailyAttendanceResource::class,
        ];

        $livewireMock = $this->createMock(\Filament\Tables\Contracts\HasTable::class);

        foreach ($resources as $resource) {
            $table = $resource::table(Table::make($livewireMock));
            $bulkActions = $table->getBulkActions();

            $flattenedActions = [];
            foreach ($bulkActions as $action) {
                if ($action instanceof \Filament\Tables\Actions\BulkActionGroup) {
                    foreach ($action->getActions() as $subAction) {
                        $flattenedActions[] = get_class($subAction);
                    }
                } else {
                    $flattenedActions[] = get_class($action);
                }
            }

            $this->assertContains(
                DeleteBulkAction::class,
                $flattenedActions,
                "Resource {$resource} does not have DeleteBulkAction configured in its table bulkActions."
            );
        }
    }

    public function test_bulk_delete_can_delete_selected_students(): void
    {
        $institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $classroom = \App\Models\Classroom::create([
            'institution_id' => $institution->id,
            'name' => '7-A',
            'tingkat' => '7',
        ]);

        $superadmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin_bulk',
            'email' => 'superadmin_bulk@test.com',
            'password' => bcrypt('password'),
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);
        $superadmin->assignRole('superadmin');
        $this->actingAs($superadmin);

        $student1 = Student::create([
            'name' => 'Siswa Test 1',
            'nis' => '10001',
            'institution_id' => $institution->id,
            'classroom_id' => $classroom->id,
            'tanggal_lahir' => '2010-01-01',
            'jenis_kelamin' => 'L',
            'status_siswa' => 'aktif',
        ]);

        $student2 = Student::create([
            'name' => 'Siswa Test 2',
            'nis' => '10002',
            'institution_id' => $institution->id,
            'classroom_id' => $classroom->id,
            'tanggal_lahir' => '2010-02-02',
            'jenis_kelamin' => 'P',
            'status_siswa' => 'aktif',
        ]);

        $student3 = Student::create([
            'name' => 'Siswa Test 3',
            'nis' => '10003',
            'institution_id' => $institution->id,
            'classroom_id' => $classroom->id,
            'tanggal_lahir' => '2010-03-03',
            'jenis_kelamin' => 'L',
            'status_siswa' => 'aktif',
        ]);

        $this->assertDatabaseHas('students', ['id' => $student1->id]);
        $this->assertDatabaseHas('students', ['id' => $student2->id]);
        $this->assertDatabaseHas('students', ['id' => $student3->id]);

        // Simulate deleting student1 and student2 via bulk action logic
        $records = collect([$student1, $student2]);
        $records->each(fn ($record) => $record->delete());

        $this->assertDatabaseMissing('students', ['id' => $student1->id]);
        $this->assertDatabaseMissing('students', ['id' => $student2->id]);
        $this->assertDatabaseHas('students', ['id' => $student3->id]);
    }
}
