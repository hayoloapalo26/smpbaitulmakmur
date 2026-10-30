<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\TeachingJournalResource;
use App\Models\CapaianPembelajaran;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Rppm;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeachingJournal;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeachingJournalTodayScheduleDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guru;
    protected User $waka;
    protected Subject $mapelMtk;
    protected Subject $mapelIpa;
    protected Classroom $kelas7A;
    protected Classroom $kelas7B;
    protected Schedule $jadwalHariIni;
    protected Schedule $jadwalHariLain;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->guru = User::create([
            'name' => 'Ustadz Salman, S.Pd.',
            'username' => 'salman_guru',
            'email' => 'salman@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru->assignRole('guru_mapel');

        $this->waka = User::create([
            'name' => 'Ustadzah Aminah, M.Pd.',
            'username' => 'aminah_waka',
            'email' => 'aminah@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->waka->assignRole('waka_kurikulum');

        $this->mapelMtk = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK',
            'institution_id' => $this->institution->id,
        ]);

        $this->mapelIpa = Subject::create([
            'name' => 'Ilmu Pengetahuan Alam',
            'kode' => 'IPA',
            'institution_id' => $this->institution->id,
        ]);

        $this->kelas7A = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
        ]);

        $this->kelas7B = Classroom::create([
            'name' => 'VII-B',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
        ]);

        $hariIni = Schedule::getIndonesianDayName(Carbon::today());
        $hariLain = ($hariIni === 'Senin') ? 'Selasa' : 'Senin';

        // Jadwal hari ini
        $this->jadwalHariIni = Schedule::create([
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelMtk->id,
            'hari' => $hariIni,
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);

        // Jadwal hari lain
        $this->jadwalHariLain = Schedule::create([
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->kelas7B->id,
            'subject_id' => $this->mapelIpa->id,
            'hari' => $hariLain,
            'jam_ke' => 3,
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 1: Dropdown jadwal hanya memuat jadwal hari ini milik guru bersangkutan
     */
    public function test_schedule_dropdown_only_contains_today_schedules_for_teacher(): void
    {
        $this->actingAs($this->guru);

        $hariIni = Schedule::getIndonesianDayName(Carbon::today());

        Livewire::test(TeachingJournalResource\Pages\ManageTeachingJournals::class)
            ->mountAction('create')
            ->assertActionMounted('create')
            // Dropdown jadwal hari ini harus ada jadwal kelas 7A matematika
            ->assertSee($this->kelas7A->name)
            ->assertSee($this->mapelMtk->name)
            // Jadwal hari lain (IPA Kelas 7B) TIDAK boleh ada di dropdown jadwal hari ini
            ->assertDontSee("Kelas {$this->kelas7B->name} — {$this->mapelIpa->name}");
    }

    /**
     * Test 2: Memilih jadwal hari ini otomatis mengisi classroom_id, subject_id, dan jam_ke
     */
    public function test_selecting_schedule_auto_populates_classroom_subject_and_jam(): void
    {
        $this->actingAs($this->guru);

        Livewire::test(TeachingJournalResource\Pages\ManageTeachingJournals::class)
            ->mountAction('create')
            ->setActionData([
                'schedule_id' => $this->jadwalHariIni->id,
            ])
            ->assertActionDataSet([
                'classroom_id' => $this->kelas7A->id,
                'subject_id' => $this->mapelMtk->id,
                'jam_ke' => 1,
            ]);
    }

    /**
     * Test 3: Pengisian jurnal untuk jadwal hari ini berhasil disimpan
     */
    public function test_teacher_can_successfully_create_journal_for_today(): void
    {
        $this->actingAs($this->guru);

        Livewire::test(TeachingJournalResource\Pages\ManageTeachingJournals::class)
            ->mountAction('create')
            ->setActionData([
                'tanggal' => Carbon::today()->toDateString(),
                'schedule_id' => $this->jadwalHariIni->id,
                'classroom_id' => $this->kelas7A->id,
                'subject_id' => $this->mapelMtk->id,
                'jam_ke' => 1,
                'materi' => 'Bilangan Bulat dan Operasinya',
                'rencana_kegiatan_guru' => 'Menerangkan konsep garis bilangan',
                'kegiatan_siswa' => 'Mengerjakan soal latihan',
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('teaching_journals', [
            'teacher_id' => $this->guru->id,
            'schedule_id' => $this->jadwalHariIni->id,
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelMtk->id,
            'tanggal' => Carbon::today()->toDateString(),
        ]);
    }

    /**
     * Test 4: Jadwal yang sudah diisi hari ini ditandai sudah diisi
     */
    public function test_filled_schedule_is_marked_as_already_filled_today(): void
    {
        $this->actingAs($this->guru);

        // Buat jurnal untuk jadwal hari ini
        TeachingJournal::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'schedule_id' => $this->jadwalHariIni->id,
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelMtk->id,
            'jam_ke' => 1,
            'tanggal' => Carbon::today()->toDateString(),
            'materi' => 'Bilangan Bulat',
            'rencana_kegiatan_guru' => 'Menerangkan konsep',
            'kegiatan_siswa' => 'Latihan',
        ]);

        Livewire::test(TeachingJournalResource\Pages\ManageTeachingJournals::class)
            ->mountAction('create')
            ->assertSee('Sudah Diisi Hari Ini');
    }
}
