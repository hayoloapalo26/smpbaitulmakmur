<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\NewsResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SidebarTupoksiStylingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
    }

    public function test_news_resource_is_grouped_under_pengumuman_dan_informasi(): void
    {
        $this->assertEquals('Pengumuman & Informasi', NewsResource::getNavigationGroup());
    }

    public function test_blade_renders_tupoksi_guru_correctly(): void
    {
        $guruGroups = [
            'Akademik & Kurikulum',
            'Fitur Wali Kelas',
            'Fitur Guru Al-Qur\'an',
            'Bab 2: Perencanaan Administrasi Pembelajaran',
            'Penilaian & Kompetensi',
            'Tahfidz & Tahsin',
            'Tugas & Kewajiban',
            'Piket',
        ];

        foreach ($guruGroups as $group) {
            $html = Blade::render(
                '<x-filament-panels::sidebar.group :label="$label" :items="[]" />',
                ['label' => $group]
            );

            $this->assertStringContainsString('data-tupoksi="guru"', $html, "Group [{$group}] should have data-tupoksi=\"guru\"");
            $this->assertStringContainsString('tupoksi-badge-guru', $html, "Group [{$group}] should have class tupoksi-badge-guru");
            $this->assertStringContainsString('TUGAS GURU', $html, "Group [{$group}] should have badge text 'TUGAS GURU'");
        }
    }

    public function test_blade_renders_tupoksi_jabatan_correctly(): void
    {
        $jabatanGroups = [
            'Bab 1: Program Kerja WKr',
            'Kesiswaan & Karakter',
            'Sarpras',
            'Humas',
            'Tata Usaha',
            'Manajemen Sekolah',
            'Kepala Sekolah',
            'Perizinan',
            'Lintas Divisi',
        ];

        foreach ($jabatanGroups as $group) {
            $html = Blade::render(
                '<x-filament-panels::sidebar.group :label="$label" :items="[]" />',
                ['label' => $group]
            );

            $this->assertStringContainsString('data-tupoksi="jabatan"', $html, "Group [{$group}] should have data-tupoksi=\"jabatan\"");
            $this->assertStringContainsString('tupoksi-badge-jabatan', $html, "Group [{$group}] should have class tupoksi-badge-jabatan");
            $this->assertStringContainsString('TUGAS JABATAN', $html, "Group [{$group}] should have badge text 'TUGAS JABATAN'");
        }
    }

    public function test_blade_renders_tupoksi_umum_dan_informasi_correctly(): void
    {
        $umumGroups = [
            'Menu Utama',
            'Pengumuman & Informasi',
            'Informasi',
            'Visi & Misi Sekolah',
            'Administrasi & Data Induk',
            'Pengaturan',
        ];

        foreach ($umumGroups as $group) {
            $html = Blade::render(
                '<x-filament-panels::sidebar.group :label="$label" :items="[]" />',
                ['label' => $group]
            );

            $this->assertStringContainsString('data-tupoksi="umum"', $html, "Group [{$group}] should have data-tupoksi=\"umum\"");
            $this->assertStringContainsString('tupoksi-badge-umum', $html, "Group [{$group}] should have class tupoksi-badge-umum");
            $this->assertStringContainsString('UMUM / INFO', $html, "Group [{$group}] should have badge text 'UMUM / INFO'");
        }
    }
}
