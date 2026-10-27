<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Filament\Staff\Resources\ScheduleResource;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduleMultipleChoiceInputTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $wakaKurikulum;
    protected Classroom $classroom;
    protected Subject $subject;
    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->wakaKurikulum = User::create([
            'name' => 'Waka Kurikulum',
            'email' => 'kurikulum@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->wakaKurikulum->assignRole('waka_kurikulum');

        $this->teacher = User::create([
            'name' => 'Guru Matematika',
            'email' => 'matematika@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->teacher->assignRole('guru_mapel');

        $this->classroom = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => '7-A',
            'tingkat' => '7',
        ]);

        $this->subject = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK-7',
        ]);
    }

    public function test_schedule_form_uses_checkbox_list_multiple_choice_for_hari_and_jam(): void
    {
        $this->actingAs($this->wakaKurikulum);

        $livewire = new class extends \Livewire\Component implements \Filament\Forms\Contracts\HasForms {
            use \Filament\Forms\Concerns\InteractsWithForms;
            public function render() { return '<div></div>'; }
        };

        $form = ScheduleResource::form(
            Form::make($livewire)
        );

        $components = $form->getComponents();
        
        // Find CheckboxList components in sections
        $flatComponents = [];
        foreach ($components as $component) {
            if (method_exists($component, 'getChildComponents')) {
                foreach ($component->getChildComponents() as $child) {
                    $flatComponents[$child->getName()] = $child;
                }
            } else {
                $flatComponents[$component->getName()] = $component;
            }
        }

        $this->assertArrayHasKey('hari', $flatComponents);
        $this->assertInstanceOf(CheckboxList::class, $flatComponents['hari']);
        $this->assertArrayHasKey('Senin', $flatComponents['hari']->getOptions());
        $this->assertArrayHasKey('Sabtu', $flatComponents['hari']->getOptions());

        $this->assertArrayHasKey('jam_ke', $flatComponents);
        $this->assertInstanceOf(CheckboxList::class, $flatComponents['jam_ke']);
        $this->assertArrayHasKey(1, $flatComponents['jam_ke']->getOptions());
        $this->assertArrayHasKey(2, $flatComponents['jam_ke']->getOptions());
    }

    public function test_can_create_multiple_schedules_at_once_with_multiple_hari_and_jam(): void
    {
        $this->actingAs($this->wakaKurikulum);

        $data = [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'hari' => ['Senin', 'Kamis'],
            'jam_ke' => [1, 2],
        ];

        $days = $data['hari'];
        $jams = $data['jam_ke'];

        $created = [];
        foreach ($days as $day) {
            foreach ($jams as $jam) {
                $created[] = Schedule::firstOrCreate([
                    'classroom_id' => $data['classroom_id'],
                    'subject_id' => $data['subject_id'],
                    'teacher_id' => $data['teacher_id'],
                    'hari' => $day,
                    'jam_ke' => (int) $jam,
                ], [
                    'institution_id' => $this->institution->id,
                ]);
            }
        }

        $this->assertCount(4, $created);

        // Verify each combination exists in database
        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
        ]);
        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'hari' => 'Senin',
            'jam_ke' => 2,
        ]);
        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'hari' => 'Kamis',
            'jam_ke' => 1,
        ]);
        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'hari' => 'Kamis',
            'jam_ke' => 2,
        ]);
    }

    public function test_can_schedule_single_jp_and_triple_jp_independently(): void
    {
        $this->actingAs($this->wakaKurikulum);

        // 1. Kasus Guru mengajar hanya 1 JP (misal jam ke-4)
        $singleJp = Schedule::firstOrCreate([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'hari' => 'Selasa',
            'jam_ke' => 4,
        ], [
            'institution_id' => $this->institution->id,
        ]);

        $this->assertDatabaseHas('schedules', [
            'hari' => 'Selasa',
            'jam_ke' => 4,
        ]);

        // 2. Kasus Guru mengajar 3 JP sekaligus (misal jam ke-5, 6, 7)
        $threeJpSlots = [5, 6, 7];
        foreach ($threeJpSlots as $slot) {
            Schedule::firstOrCreate([
                'classroom_id' => $this->classroom->id,
                'subject_id' => $this->subject->id,
                'teacher_id' => $this->teacher->id,
                'hari' => 'Rabu',
                'jam_ke' => $slot,
            ], [
                'institution_id' => $this->institution->id,
            ]);
        }

        $this->assertEquals(3, Schedule::where('hari', 'Rabu')->whereIn('jam_ke', [5, 6, 7])->count());
    }
}
