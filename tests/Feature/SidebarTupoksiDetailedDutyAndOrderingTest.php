<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\PiketDashboard;
use App\Models\DutySchedule;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SidebarTupoksiDetailedDutyAndOrderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
    }

    public function test_navigation_groups_order_places_informasi_umum_first_then_tugas_guru_then_tugas_jabatan(): void
    {
        $panel = Filament::getPanel('staff');
        $groups = array_values($panel->getNavigationGroups());

        // Helper untuk mencari index grup
        $indexOf = fn(string $name) => array_search($name, $groups);

        // 1. Informasi Umum berada di paling atas
        $idxMenuUtama = $indexOf('Menu Utama');
        $idxPengumuman = $indexOf('Pengumuman & Informasi');
        $idxVisiMisi = $indexOf('Visi & Misi Sekolah');

        $this->assertNotFalse($idxMenuUtama);
        $this->assertNotFalse($idxPengumuman);
        $this->assertNotFalse($idxVisiMisi);

        // 2. Tugas Guru berada setelah Informasi Umum
        $idxTugasKewajiban = $indexOf('Tugas & Kewajiban Harian Guru') !== false
            ? $indexOf('Tugas & Kewajiban Harian Guru')
            : $indexOf('Tugas & Kewajiban');
        $idxMapel = $indexOf('Akademik & Kurikulum');
        $idxQuran = $indexOf('Fitur Guru Al-Qur\'an');
        $idxWaliKelas = $indexOf('Fitur Wali Kelas');
        $idxPiket = $indexOf('Tugas Guru Piket');

        $this->assertNotFalse($idxTugasKewajiban);
        $this->assertNotFalse($idxMapel);
        $this->assertNotFalse($idxQuran);
        $this->assertNotFalse($idxWaliKelas);
        $this->assertNotFalse($idxPiket);

        // Pastikan Tugas Guru setelah Informasi Umum
        $this->assertGreaterThan($idxVisiMisi, $idxTugasKewajiban);
        $this->assertGreaterThan($idxVisiMisi, $idxMapel);
        $this->assertGreaterThan($idxVisiMisi, $idxQuran);
        $this->assertGreaterThan($idxVisiMisi, $idxWaliKelas);
        $this->assertGreaterThan($idxVisiMisi, $idxPiket);

        // 3. Tugas Jabatan berada setelah Tugas Guru
        $idxKepsek = $indexOf('Manajemen Sekolah');
        $idxKesiswaan = $indexOf('Kesiswaan & Karakter');
        $idxSarpras = $indexOf('Sarpras');
        $idxHumas = $indexOf('Humas');
        $idxTataUsaha = $indexOf('Tata Usaha');
        $idxMadin = $indexOf('Madrasah Diniyah (Madin)');

        $this->assertNotFalse($idxKepsek);
        $this->assertNotFalse($idxKesiswaan);
        $this->assertNotFalse($idxSarpras);
        $this->assertNotFalse($idxHumas);
        $this->assertNotFalse($idxTataUsaha);
        $this->assertNotFalse($idxMadin);

        // Pastikan Tugas Jabatan berada setelah grup tugas guru
        $maxTugasGuruIdx = max($idxTugasKewajiban, $idxMapel, $idxQuran, $idxWaliKelas, $idxPiket);
        $this->assertGreaterThan($maxTugasGuruIdx, $idxKepsek);
        $this->assertGreaterThan($maxTugasGuruIdx, $idxKesiswaan);
        $this->assertGreaterThan($maxTugasGuruIdx, $idxSarpras);
        $this->assertGreaterThan($maxTugasGuruIdx, $idxHumas);
        $this->assertGreaterThan($maxTugasGuruIdx, $idxTataUsaha);
        $this->assertGreaterThan($maxTugasGuruIdx, $idxMadin);

        // 4. Pengaturan berada di paling akhir
        $idxPengaturan = $indexOf('Pengaturan');
        $this->assertNotFalse($idxPengaturan);
        $this->assertGreaterThan($idxTataUsaha, $idxPengaturan);
    }

    public function test_piket_group_renders_detailed_badge_and_keterangan(): void
    {
        $html = Blade::render(
            '<x-filament-panels::sidebar.group :label="$label" :items="[]" />',
            ['label' => 'Tugas Guru Piket']
        );

        $this->assertStringContainsString('data-tupoksi="guru"', $html);
        $this->assertStringContainsString('TUGAS GURU PIKET', $html);
        $this->assertStringContainsString('Tugas &amp; pemantauan piket harian', $html);
    }

    public function test_piket_when_teacher_is_on_duty_today_shows_active_indicator(): void
    {
        $institution = \App\Models\Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smpi']
        );

        Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'institution_id' => $institution->id,
        ]);
        $user->assignRole('guru_piket');

        $hariIni = Schedule::getIndonesianDayName(Carbon::today());
        DutySchedule::create([
            'hari' => $hariIni,
            'teacher_id' => $user->id,
            'institution_id' => $institution->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($user);
        $this->assertTrue($user->isPiketToday());

        // Periksa badge pada Dasbor Piket
        $badge = PiketDashboard::getNavigationBadge();
        $this->assertEquals('⚡ Bertugas Hari Ini', $badge);

        // Periksa rendering Group Header ketika guru piket sedang bertugas aktif hari ini
        $html = Blade::render(
            '<x-filament-panels::sidebar.group :label="$label" :items="[]" />',
            ['label' => 'Tugas Guru Piket']
        );

        $this->assertStringContainsString('TUGAS GURU PIKET (AKTIF)', $html);
        $this->assertStringContainsString('Anda bertugas piket hari ini', $html);
        $this->assertStringContainsString('tupoksi-badge-piket-aktif', $html);
    }

    public function test_detailed_tupoksi_badges_and_descriptions_for_all_categories(): void
    {
        $testCases = [
            'Akademik & Kurikulum' => [
                'badge' => 'TUGAS GURU MAPEL',
                'keterangan' => 'Jurnal mengajar, nilai & perangkat ajar',
            ],
            'Fitur Guru Al-Qur\'an' => [
                'badge' => 'TUGAS GURU AL-QUR\'AN',
                'keterangan' => 'Ziyadah, muraja\'ah & mutu bacaan santri',
            ],
            'Fitur Wali Kelas' => [
                'badge' => 'TUGAS GURU WALI KELAS',
                'keterangan' => 'Binaan kelas, rekap nilai & profil lulusan',
            ],
            'Manajemen Sekolah' => [
                'badge' => 'TUGAS JABATAN (KEPALA SEKOLAH)',
                'keterangan' => 'Manajemen sekolah, supervisi & akreditasi',
            ],
            'Bab 1: Program Kerja WKr' => [
                'badge' => 'TUGAS JABATAN (WAKA KURIKULUM)',
                'keterangan' => 'Setting guru mapel, kurikulum & jadwal',
            ],
            'Kesiswaan & Karakter' => [
                'badge' => 'TUGAS JABATAN (WAKA KESISWAAN)',
                'keterangan' => 'Disiplin, presensi, karakter & prestasi siswa',
            ],
            'Sarpras' => [
                'badge' => 'TUGAS JABATAN (WAKA SARPRAS)',
                'keterangan' => 'Inventaris, pengadaan & perawatan sarpras',
            ],
            'Humas' => [
                'badge' => 'TUGAS JABATAN (WAKA HUMAS)',
                'keterangan' => 'Kemitraan, publikasi & paguyuban wali murid',
            ],
            'Tata Usaha' => [
                'badge' => 'TUGAS JABATAN (TATA USAHA)',
                'keterangan' => 'Administrasi kepegawaian, arsip & persuratan',
            ],
            'Madrasah Diniyah (Madin)' => [
                'badge' => 'TUGAS JABATAN (KOORDINATOR MADIN)',
                'keterangan' => 'Penerimaan nilai & program diniyah',
            ],
        ];

        foreach ($testCases as $label => $expected) {
            $html = Blade::render(
                '<x-filament-panels::sidebar.group :label="$label" :items="[]" />',
                ['label' => $label]
            );

            $decodedHtml = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $this->assertStringContainsString($expected['badge'], $decodedHtml, "Label [{$label}] should have badge [{$expected['badge']}]");
            $this->assertStringContainsString($expected['keterangan'], $decodedHtml, "Label [{$label}] should have keterangan [{$expected['keterangan']}]");
        }
    }
}
