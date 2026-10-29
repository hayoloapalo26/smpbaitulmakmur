<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\WorkProgramReportPage;
use App\Models\Institution;
use App\Models\ProgramKurikulum;
use App\Models\ProgramKepsek;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkProgramReportDriveLinkTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $principal;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->principal = User::create([
            'name' => 'Dr. H. Kepala Sekolah',
            'username' => 'kepsek_test',
            'email' => 'kepsek_drive@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->principal->assignRole('kepala_sekolah');
        $this->actingAs($this->principal);
    }

    public function test_monitoring_pelaksanaan_program_page_renders_and_contains_program_links_and_drive(): void
    {
        $progKurikulum = ProgramKurikulum::create([
            'user_id' => $this->principal->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Penyusunan Modul Ajar Digital',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'tujuan' => 'Meningkatkan kesiapan ajar digital',
            'status_pelaksanaan' => 'Selesai',
            'dokumen_terkait' => 'https://drive.google.com/drive/folders/test123456',
        ]);

        $progRencana = ProgramKepsek::create([
            'user_id' => $this->principal->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Supervisi Akademik Semester Ganjil',
            'kategori' => 'Manajerial',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Rutin',
            'tujuan' => 'Supervisi pembelajaran guru',
            'status_pelaksanaan' => 'Belum',
            'dokumen_terkait' => null,
        ]);

        $livewire = Livewire::test(WorkProgramReportPage::class);
        $livewire->assertSuccessful();

        $viewData = $livewire->instance()->getViewData();
        $this->assertGreaterThanOrEqual(2, $viewData['totalProgram']);

        $daftar = $viewData['daftarProgram'];
        $itemSelesai = $daftar->firstWhere('nama', 'Penyusunan Modul Ajar Digital');
        $this->assertNotNull($itemSelesai);
        $this->assertNotEmpty($itemSelesai->url);
        $this->assertEquals('https://drive.google.com/drive/folders/test123456', $itemSelesai->link_drive);

        $itemRencana = $daftar->firstWhere('nama', 'Supervisi Akademik Semester Ganjil');
        $this->assertNotNull($itemRencana);
        $this->assertNotEmpty($itemRencana->url);
        $this->assertNull($itemRencana->link_drive);
    }

    public function test_modal_input_link_drive_updates_database_successfully(): void
    {
        $prog = ProgramKepsek::create([
            'user_id' => $this->principal->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Akreditasi Perpustakaan Sekolah',
            'kategori' => 'Manajerial',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Strategis',
            'tujuan' => 'Persiapan akreditasi perpus',
            'status_pelaksanaan' => 'Belum',
            'dokumen_terkait' => null,
        ]);

        $driveUrl = 'https://drive.google.com/drive/folders/akreditasi-perpus-2026';

        Livewire::test(WorkProgramReportPage::class)
            ->call('openDriveModal', ProgramKepsek::class, $prog->id, $prog->nama_program, '')
            ->assertSet('showDriveModal', true)
            ->assertSet('modalProgramName', 'Akreditasi Perpustakaan Sekolah')
            ->set('modalDriveUrl', $driveUrl)
            ->call('saveDriveModal')
            ->assertSet('showDriveModal', false)
            ->assertHasNoErrors();

        $prog->refresh();
        $this->assertEquals($driveUrl, $prog->dokumen_terkait);

        // Pastikan muncul di getViewData
        $livewire = Livewire::test(WorkProgramReportPage::class);
        $viewData = $livewire->instance()->getViewData();
        $savedItem = $viewData['daftarProgram']->firstWhere('id', $prog->id);
        $this->assertEquals($driveUrl, $savedItem->link_drive);
    }
}
