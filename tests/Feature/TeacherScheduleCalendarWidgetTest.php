<?php

namespace Tests\Feature;

use App\Filament\Staff\Widgets\TeacherScheduleCalendarWidget;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherScheduleCalendarWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guru;
    protected Classroom $kelas7A;
    protected Classroom $kelas8B;
    protected Subject $mapelMtk;
    protected Subject $mapelIpa;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->guru = User::factory()->create([
            'name' => 'Ustadz Ahmad Fauzi',
            'email' => 'ahmad.fauzi@smpit.test',
            'institution_id' => $this->institution->id,
        ]);
        $this->guru->assignRole('guru_mapel');

        $this->kelas7A = Classroom::create([
            'name' => '7A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);

        $this->kelas8B = Classroom::create([
            'name' => '8B',
            'tingkat' => '8',
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);

        $this->mapelMtk = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK-7',
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);

        $this->mapelIpa = Subject::create([
            'name' => 'Ilmu Pengetahuan Alam',
            'kode' => 'IPA-8',
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);

        // Jadwal Senin: 2 JP di 7A (jam 1, 2) dan 2 JP di 8B (jam 3, 4)
        Schedule::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelMtk->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        Schedule::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelMtk->id,
            'hari' => 'Senin',
            'jam_ke' => 2,
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        Schedule::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->kelas8B->id,
            'subject_id' => $this->mapelIpa->id,
            'hari' => 'Senin',
            'jam_ke' => 3,
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        Schedule::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->kelas8B->id,
            'subject_id' => $this->mapelIpa->id,
            'hari' => 'Senin',
            'jam_ke' => 4,
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        // Jadwal Rabu: 2 JP di 7A (jam 1, 2)
        Schedule::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelMtk->id,
            'hari' => 'Rabu',
            'jam_ke' => 1,
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        Schedule::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelMtk->id,
            'hari' => 'Rabu',
            'jam_ke' => 2,
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }

    public function test_get_schedule_data_returns_classes_and_jp_per_day(): void
    {
        $this->actingAs($this->guru);

        $widget = new TeacherScheduleCalendarWidget();
        $widget->currentMonth = 10;
        $widget->currentYear = 2026;

        $data = $widget->getScheduleData();

        $this->assertArrayHasKey('calendar', $data);
        $this->assertArrayHasKey('totalJpThisMonth', $data);

        // Cari hari Senin di bulan Oktober 2026 (misal tanggal 5 Oktober 2026 adalah Senin)
        $seninCell = collect($data['calendar'])->first(function ($day) {
            return $day['date'] === 5;
        });

        $this->assertNotNull($seninCell);
        $this->assertEquals('Senin', $seninCell['day_name']);
        $this->assertEquals(4, $seninCell['jp']);
        $this->assertCount(2, $seninCell['classes']);

        // Pastikan kelas 7A dan 8B terdaftar
        $classNames = collect($seninCell['classes'])->pluck('classroom_name')->all();
        $this->assertContains('7A', $classNames);
        $this->assertContains('8B', $classNames);

        // Rincian kelas 7A harus 2 JP
        $kelas7AData = collect($seninCell['classes'])->firstWhere('classroom_name', '7A');
        $this->assertEquals(2, $kelas7AData['jp']);
        $this->assertEquals('Matematika', $kelas7AData['subject_name']);
        $this->assertEquals('Jam Ke-1, 2', $kelas7AData['jam_text']);

        // Rincian kelas 8B harus 2 JP
        $kelas8BData = collect($seninCell['classes'])->firstWhere('classroom_name', '8B');
        $this->assertEquals(2, $kelas8BData['jp']);
        $this->assertEquals('Ilmu Pengetahuan Alam', $kelas8BData['subject_name']);
        $this->assertEquals('Jam Ke-3, 4', $kelas8BData['jam_text']);

        // Pastikan details berisi 4 item
        $this->assertCount(4, $seninCell['details']);
    }

    public function test_livewire_widget_renders_classroom_pills_on_calendar(): void
    {
        $this->actingAs($this->guru);

        Livewire::test(TeacherScheduleCalendarWidget::class)
            ->set('currentMonth', 10)
            ->set('currentYear', 2026)
            ->assertSuccessful()
            ->assertSee('7A')
            ->assertSee('8B')
            ->assertSee('Total 4')
            ->assertSee('Oktober 2026')
            ->assertSee('Rincian jadwal mengajar');
    }
}
