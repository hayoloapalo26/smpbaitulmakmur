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

class TeachingJournalRppmAndTpTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guru;
    protected Subject $subject;
    protected Classroom $classroom;
    protected Schedule $schedule;
    protected CapaianPembelajaran $cp;
    protected TujuanPembelajaran $tp1;
    protected TujuanPembelajaran $tp2;
    protected Rppm $rppm;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->guru = User::create([
            'name' => 'Ustadz Ahmad Farhan, S.Pd.',
            'username' => 'ahmad_farhan',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru->assignRole('guru_mapel');

        $this->subject = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK-7',
            'institution_id' => $this->institution->id,
        ]);

        $this->classroom = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
        ]);

        $this->schedule = Schedule::create([
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'hari' => Schedule::getIndonesianDayName(Carbon::today()),
            'jam_ke' => '1',
            'institution_id' => $this->institution->id,
        ]);

        $this->cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'fase' => 'Fase D (SMP)',
            'tingkat' => '7',
            'elemen' => 'Bilangan',
            'deskripsi_cp' => 'Peserta didik dapat mengoperasikan bilangan rasional.',
        ]);

        $this->tp1 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'capaian_pembelajaran_id' => $this->cp->id,
            'subject_id' => $this->subject->id,
            'guru_id' => $this->guru->id,
            'kode_tp' => 'TP.7.1',
            'deskripsi_tp' => 'Memahami konsep bilangan bulat dan operasi hitung dasar.',
            'materi' => 'Bilangan Bulat',
            'kelas_tingkat' => '7',
        ]);

        $this->tp2 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'capaian_pembelajaran_id' => $this->cp->id,
            'subject_id' => $this->subject->id,
            'guru_id' => $this->guru->id,
            'kode_tp' => 'TP.7.2',
            'deskripsi_tp' => 'Menyelesaikan permasalahan kontekstual menggunakan operasi bilangan bulat.',
            'materi' => 'Aplikasi Bilangan Bulat',
            'kelas_tingkat' => '7',
        ]);

        $this->rppm = Rppm::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'kelas_fase' => 'Fase D - Kelas VII',
            'mata_pelajaran' => 'Matematika',
            'topik' => 'Operasi Bilangan Bulat & Pecahan',
            'selected_tp_ids' => [$this->tp1->id, $this->tp2->id],
            'status' => 'approved',
        ]);
    }

    public function test_teaching_journal_can_be_associated_with_rppm_and_tps(): void
    {
        $journal = TeachingJournal::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'schedule_id' => $this->schedule->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => '1',
            'tanggal' => Carbon::today()->toDateString(),
            'rppm_id' => $this->rppm->id,
            'tp_ids' => [$this->tp1->id, $this->tp2->id],
            'materi' => 'Operasi Bilangan Bulat',
            'rencana_kegiatan_guru' => 'Penjelasan materi bilangan bulat',
            'kegiatan_siswa' => 'Latihan soal kelompok',
        ]);

        $this->assertDatabaseHas('teaching_journals', [
            'id' => $journal->id,
            'rppm_id' => $this->rppm->id,
            'materi' => 'Operasi Bilangan Bulat',
        ]);

        $fresh = $journal->fresh();
        $this->assertNotNull($fresh->rppm);
        $this->assertEquals('Operasi Bilangan Bulat & Pecahan', $fresh->rppm->topik);
        $this->assertIsArray($fresh->tp_ids);
        $this->assertCount(2, $fresh->tp_ids);
        $this->assertContains($this->tp1->id, $fresh->tp_ids);
        $this->assertContains($this->tp2->id, $fresh->tp_ids);

        // Accessor getSelectedTpsAttribute
        $selectedTps = $fresh->selected_tps;
        $this->assertCount(2, $selectedTps);
        $this->assertEquals('1.1', $selectedTps->first()->kode_tp);
    }

    public function test_teaching_journal_auto_populates_materi_from_rppm_if_materi_empty(): void
    {
        $journal = TeachingJournal::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'schedule_id' => $this->schedule->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => '1',
            'tanggal' => Carbon::today()->toDateString(),
            'rppm_id' => $this->rppm->id,
            'tp_ids' => [$this->tp1->id],
            'materi' => null, // Dikosongkan
            'rencana_kegiatan_guru' => 'Ceramah dan diskusi',
            'kegiatan_siswa' => 'Diskusi kelompok',
        ]);

        $this->assertEquals('Operasi Bilangan Bulat & Pecahan', $journal->fresh()->materi);
    }

    public function test_teaching_journal_resource_permissions_and_scope(): void
    {
        $this->actingAs($this->guru);

        $this->assertTrue(TeachingJournalResource::canAccess());
        $this->assertTrue(TeachingJournalResource::canCreate());

        $journal = TeachingJournal::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'schedule_id' => $this->schedule->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => '1',
            'tanggal' => Carbon::today()->toDateString(),
            'rppm_id' => $this->rppm->id,
            'tp_ids' => [$this->tp1->id, $this->tp2->id],
            'materi' => 'Operasi Bilangan Bulat',
            'rencana_kegiatan_guru' => 'Ceramah interaktif',
            'kegiatan_siswa' => 'Menyelesaikan LKS',
        ]);

        $this->assertTrue(TeachingJournalResource::canEdit($journal));
        $this->assertTrue(TeachingJournalResource::canDelete($journal));

        $queried = TeachingJournalResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($journal->id, $queried);
    }
}
