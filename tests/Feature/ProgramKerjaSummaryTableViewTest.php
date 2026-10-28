<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\BkWorkProgramResource;
use App\Filament\Staff\Resources\ClassProgramResource;
use App\Filament\Staff\Resources\ProgramHumasResource;
use App\Filament\Staff\Resources\ProgramKepsekResource;
use App\Filament\Staff\Resources\ProgramKesiswaanResource;
use App\Filament\Staff\Resources\ProgramKurikulumResource;
use App\Filament\Staff\Resources\ProgramSarprasResource;
use App\Filament\Staff\Resources\WorkProgramResource;
use App\Models\Institution;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use App\Models\User;
use Filament\Infolists\Infolist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProgramKerjaSummaryTableViewTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $principal;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sarpras', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->principal = User::create([
            'name' => 'Kepala Sekolah',
            'username' => 'kepsek_summary_test',
            'email' => 'kepsek_summary@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->principal->assignRole('kepala_sekolah');
        $this->actingAs($this->principal);
    }

    public function test_all_program_kerja_resources_have_summary_table_infolist(): void
    {
        $resources = [
            ProgramKurikulumResource::class,
            ProgramKesiswaanResource::class,
            ProgramSarprasResource::class,
            ProgramHumasResource::class,
            ProgramKepsekResource::class,
            BkWorkProgramResource::class,
            ClassProgramResource::class,
            WorkProgramResource::class,
        ];

        $livewire = new class extends \Livewire\Component implements \Filament\Infolists\Contracts\HasInfolists {
            use \Filament\Infolists\Concerns\InteractsWithInfolists;
            public function render() { return '<div></div>'; }
        };

        foreach ($resources as $resource) {
            $infolist = $resource::infolist(Infolist::make($livewire));
            $components = $infolist->getComponents();

            $this->assertNotEmpty($components, "Resource {$resource} harus memiliki komponen infolist");
            $viewEntry = $components[0];
            $this->assertInstanceOf(\Filament\Infolists\Components\ViewEntry::class, $viewEntry);
            $this->assertEquals(
                'filament.staff.components.program-kerja-summary-table',
                $viewEntry->getView(),
                "Resource {$resource} harus mengarahkan ke view program-kerja-summary-table"
            );
        }
    }

    public function test_program_kurikulum_summary_table_blade_renders_as_pure_table_without_inputs(): void
    {
        $program = ProgramKurikulum::create([
            'user_id' => $this->principal->id,
            'institution_id' => $this->institution->id,
            'nama_program' => 'Workshop Kurikulum Merdeka Terintegrasi Adab',
            'tahun_ajaran' => '2026/2027',
            'kategori' => 'Pengembangan SDM/Guru',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'sasaran' => 'Seluruh Dewan Guru',
            'waktu_pelaksanaan' => 'Juli 2026',
            'estimasi_anggaran' => 5000000,
            'status_pelaksanaan' => 'Sedang Berjalan',
            'status_persetujuan' => 'Disetujui',
            'catatan_kepsek' => 'Segera siapkan narasumber ahli dan modul praktiknya.',
            'tujuan' => 'Meningkatkan kompetensi pedagogik guru dalam merancang modul ajar berbasis nilai-nilai Islam.',
            'teknis_pelaksanaan' => '<ol><li>Koordinasi tim</li><li>Pelaksanaan pelatihan 2 hari</li></ol>',
            'dokumen_terkait' => 'https://drive.google.com/test-doc',
        ]);

        $renderedHtml = view('filament.staff.components.program-kerja-summary-table', [
            'getRecord' => fn () => $program,
            'record' => $program,
        ])->render();

        // 1. Harus berupa tabel
        $this->assertStringContainsString('<table', $renderedHtml, 'Output harus mengandung elemen <table>');
        $this->assertStringContainsString('Workshop Kurikulum Merdeka Terintegrasi Adab', $renderedHtml);
        $this->assertStringContainsString('Kurikulum', $renderedHtml);
        $this->assertStringContainsString('Rp 5.000.000', $renderedHtml);
        $this->assertStringContainsString('Disetujui', $renderedHtml);
        $this->assertStringContainsString('Segera siapkan narasumber ahli', $renderedHtml);

        // 2. Bukan berupa inputan form disabled
        $this->assertStringNotContainsString('<input', $renderedHtml, 'Tampilan ringkasan tabel tidak boleh menggunakan <input>');
        $this->assertStringNotContainsString('<select', $renderedHtml, 'Tampilan ringkasan tabel tidak boleh menggunakan <select>');
        $this->assertStringNotContainsString('<textarea', $renderedHtml, 'Tampilan ringkasan tabel tidak boleh menggunakan <textarea>');
    }

    public function test_program_sarpras_summary_table_blade_renders_insidentil_correctly(): void
    {
        $sarpras = ProgramSarpras::create([
            'user_id' => $this->principal->id,
            'institution_id' => $this->institution->id,
            'nama_program' => 'Perbaikan Darurat Instalasi Listrik Laboratorium',
            'tahun_ajaran' => '2026/2027',
            'kategori' => 'Pemeliharaan',
            'tipe_pelaksanaan' => 'Insidentil',
            'sifat' => 'Wajib',
            'sasaran' => 'Laboratorium Komputer & IPA',
            'waktu_pelaksanaan' => 'Minggu ini',
            'estimasi_anggaran' => 1500000,
            'status_pelaksanaan' => 'Rencana',
            'tujuan' => 'Mencegah konsleting listrik dan menjaga keamanan laboratorium.',
        ]);

        $renderedHtml = view('filament.staff.components.program-kerja-summary-table', [
            'getRecord' => fn () => $sarpras,
            'record' => $sarpras,
        ])->render();

        $this->assertStringContainsString('<table', $renderedHtml);
        $this->assertStringContainsString('Perbaikan Darurat Instalasi Listrik Laboratorium', $renderedHtml);
        $this->assertStringContainsString('Sarana &amp; Prasarana (Sarpras)', $renderedHtml);
        $this->assertStringContainsString('Insidentil', $renderedHtml);
        $this->assertStringContainsString('Rp 1.500.000', $renderedHtml);

        // Pastikan tidak ada inputan
        $this->assertStringNotContainsString('<input', $renderedHtml);
        $this->assertStringNotContainsString('<select', $renderedHtml);
        $this->assertStringNotContainsString('<textarea', $renderedHtml);
    }
}
