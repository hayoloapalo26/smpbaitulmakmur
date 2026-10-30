<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\AlurTujuanPembelajaranResource;
use App\Filament\Staff\Resources\CapaianPembelajaranResource;
use App\Filament\Staff\Resources\TujuanPembelajaranResource;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Rppm;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CpTpAtpCodeSequencingTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guru;
    protected Subject $subject;

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
            'name' => 'Ustadz Zaid, S.Pd.',
            'username' => 'ustadz_zaid',
            'email' => 'zaid@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru->assignRole('guru_mapel');

        $this->subject = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK',
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 1: Input 1 CP otomatis mendapatkan kode 1, dan CP berikutnya mendapatkan kode 2
     */
    public function test_cp_gets_sequential_code_1_then_2(): void
    {
        $cp1 = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Bilangan',
            'deskripsi_cp' => 'Peserta didik memahami bilangan bulat dan pecahan.',
        ]);

        $this->assertEquals('1', $cp1->kode_cp, 'CP pertama harus memiliki kode 1');

        $cp2 = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Aljabar',
            'deskripsi_cp' => 'Peserta didik memahami konsep aljabar dan persamaannya.',
        ]);

        $this->assertEquals('2', $cp2->kode_cp, 'CP kedua harus memiliki kode 2');
    }

    /**
     * Test 2: Input 5 TP dengan CP ke-1 menghasilkan kode 1.1 s/d 1.5, dan TP pada CP ke-2 menghasilkan 2.1 dst.
     */
    public function test_tp_gets_hierarchical_code_based_on_cp(): void
    {
        $cp1 = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Bilangan',
            'deskripsi_cp' => 'Peserta didik memahami bilangan bulat dan pecahan.',
        ]);

        $tpCodesCp1 = [];
        for ($i = 1; $i <= 5; $i++) {
            $tp = TujuanPembelajaran::create([
                'institution_id' => $this->institution->id,
                'guru_id' => $this->guru->id,
                'subject_id' => $this->subject->id,
                'capaian_pembelajaran_id' => $cp1->id,
                'kelas_tingkat' => '7',
                'materi' => "Materi TP Bilangan {$i}",
                'deskripsi_tp' => "Peserta didik dapat menyelesaikan operasi bilangan bagian {$i}",
            ]);
            $tpCodesCp1[] = $tp->kode_tp;
        }

        $this->assertEquals(['1.1', '1.2', '1.3', '1.4', '1.5'], $tpCodesCp1, '5 TP dari CP 1 harus berkode 1.1 s/d 1.5');

        $cp2 = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Aljabar',
            'deskripsi_cp' => 'Peserta didik memahami konsep aljabar dan persamaannya.',
        ]);

        $tpCodesCp2 = [];
        for ($j = 1; $j <= 2; $j++) {
            $tp = TujuanPembelajaran::create([
                'institution_id' => $this->institution->id,
                'guru_id' => $this->guru->id,
                'subject_id' => $this->subject->id,
                'capaian_pembelajaran_id' => $cp2->id,
                'kelas_tingkat' => '7',
                'materi' => "Materi TP Aljabar {$j}",
                'deskripsi_tp' => "Peserta didik dapat menyelesaikan operasi aljabar bagian {$j}",
            ]);
            $tpCodesCp2[] = $tp->kode_tp;
        }

        $this->assertEquals(['2.1', '2.2'], $tpCodesCp2, 'TP dari CP 2 harus berkode 2.1 dan 2.2');
    }

    /**
     * Test 3: ATP mengumpulkan TP sesuai CP dan mewarisi kode yang sesuai dengan CP & ATP (ATP 1 untuk CP 1, ATP 2 untuk CP 2)
     */
    public function test_atp_accumulates_all_tps_with_correct_codes_and_order(): void
    {
        $cp1 = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Bilangan',
            'deskripsi_cp' => 'Peserta didik memahami bilangan bulat.',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            TujuanPembelajaran::create([
                'institution_id' => $this->institution->id,
                'guru_id' => $this->guru->id,
                'subject_id' => $this->subject->id,
                'capaian_pembelajaran_id' => $cp1->id,
                'kelas_tingkat' => '7',
                'materi' => "Materi {$i}",
                'deskripsi_tp' => "Deskripsi TP 1.{$i}",
            ]);
        }

        $cp2 = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Aljabar',
            'deskripsi_cp' => 'Peserta didik memahami konsep aljabar.',
        ]);

        for ($j = 1; $j <= 2; $j++) {
            TujuanPembelajaran::create([
                'institution_id' => $this->institution->id,
                'guru_id' => $this->guru->id,
                'subject_id' => $this->subject->id,
                'capaian_pembelajaran_id' => $cp2->id,
                'kelas_tingkat' => '7',
                'materi' => "Aljabar {$j}",
                'deskripsi_tp' => "Deskripsi TP 2.{$j}",
            ]);
        }

        // ATP untuk CP 1
        $atp1 = AlurTujuanPembelajaran::where('guru_id', $this->guru->id)
            ->where('subject_id', $this->subject->id)
            ->where('capaian_pembelajaran_id', $cp1->id)
            ->first();

        $this->assertNotNull($atp1, 'ATP untuk CP 1 harus otomatis tersinkronisasi');
        $this->assertEquals('1', $atp1->kode_atp, 'Kode ATP harus sesuai nomor CP 1');
        $this->assertCount(5, $atp1->tp_ids, 'ATP 1 harus mengumpulkan 5 TP dari CP 1');
        $this->assertEquals(['1.1', '1.2', '1.3', '1.4', '1.5'], $atp1->accumulated_tps->pluck('kode_tp')->toArray());
        $this->assertStringContainsString('1.1, 1.2, 1.3, 1.4, 1.5', $atp1->tp_codes_summary);

        // ATP untuk CP 2
        $atp2 = AlurTujuanPembelajaran::where('guru_id', $this->guru->id)
            ->where('subject_id', $this->subject->id)
            ->where('capaian_pembelajaran_id', $cp2->id)
            ->first();

        $this->assertNotNull($atp2, 'ATP untuk CP 2 harus otomatis tersinkronisasi');
        $this->assertEquals('2', $atp2->kode_atp, 'Kode ATP harus sesuai nomor CP 2');
        $this->assertCount(2, $atp2->tp_ids, 'ATP 2 harus mengumpulkan 2 TP dari CP 2');
        $this->assertEquals(['2.1', '2.2'], $atp2->accumulated_tps->pluck('kode_tp')->toArray());

        // Verifikasi urutan_alur pada ATP 1
        $this->assertIsArray($atp1->urutan_alur);
        $this->assertCount(5, $atp1->urutan_alur);
        $this->assertEquals('1.1', $atp1->urutan_alur[0]['kode_tp']);
        $this->assertEquals('1', $atp1->urutan_alur[0]['kode_cp']);
        $this->assertEquals('1.5', $atp1->urutan_alur[4]['kode_tp']);
    }

    /**
     * Test 4: RPPM menyimpan rumusan TP dengan kode [1.1], [1.2]
     */
    public function test_rppm_stores_tp_with_formatted_code(): void
    {
        $cp1 = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Bilangan',
            'deskripsi_cp' => 'Peserta didik memahami bilangan bulat.',
        ]);

        $tp = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'capaian_pembelajaran_id' => $cp1->id,
            'kelas_tingkat' => '7',
            'materi' => 'Bilangan Bulat',
            'deskripsi_tp' => 'Memahami operasi hitung bilangan bulat',
        ]);

        $this->assertEquals('1.1', $tp->kode_tp);

        $rppm = Rppm::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran' => $this->subject->name,
            'kelas_fase' => 'Kelas 7 / Fase D',
            'capaian_pembelajaran_id' => $cp1->id,
            'selected_tp_ids' => [$tp->id],
            'topik' => 'Operasi Bilangan Bulat',
            'content' => [
                'tujuan_pembelajaran' => [
                    ['rumusan' => "[{$tp->kode_tp}] ({$tp->materi}) {$tp->deskripsi_tp}"],
                ],
            ],
        ]);

        $this->assertStringContainsString('[1.1]', $rppm->content['tujuan_pembelajaran'][0]['rumusan']);
    }

    /**
     * Test 5: Filament Table pages render dengan kolom kode baru tanpa exception
     */
    public function test_filament_resources_render_without_error(): void
    {
        $this->actingAs($this->guru);

        $cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Bilangan',
            'deskripsi_cp' => 'Peserta didik memahami bilangan bulat.',
        ]);

        $tp = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'capaian_pembelajaran_id' => $cp->id,
            'kelas_tingkat' => '7',
            'materi' => 'Bilangan Bulat',
            'deskripsi_tp' => 'Memahami operasi hitung bilangan bulat',
        ]);

        Livewire::test(CapaianPembelajaranResource\Pages\ManageCapaianPembelajarans::class)
            ->assertSuccessful()
            ->assertSee('CP 1');

        Livewire::test(TujuanPembelajaranResource\Pages\ManageTujuanPembelajarans::class)
            ->assertSuccessful()
            ->assertSee('1.1');

        Livewire::test(AlurTujuanPembelajaranResource\Pages\ManageAlurTujuanPembelajarans::class)
            ->assertSuccessful()
            ->assertSee('1.1');
    }

    /**
     * Test 6: RPPM form dropdown menampilkan kode ATP dan kode CP yang bersangkutan secara otomatis
     */
    public function test_rppm_dropdown_displays_atp_and_cp_codes_and_auto_selects(): void
    {
        $this->actingAs($this->guru);

        $cp1 = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Bilangan',
            'deskripsi_cp' => 'Peserta didik memahami bilangan bulat.',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            TujuanPembelajaran::create([
                'institution_id' => $this->institution->id,
                'guru_id' => $this->guru->id,
                'subject_id' => $this->subject->id,
                'capaian_pembelajaran_id' => $cp1->id,
                'kelas_tingkat' => '7',
                'materi' => "Materi {$i}",
                'deskripsi_tp' => "Deskripsi TP 1.{$i}",
            ]);
        }

        $atp1 = AlurTujuanPembelajaran::where('capaian_pembelajaran_id', $cp1->id)->first();
        $this->assertNotNull($atp1);
        $this->assertEquals('1', $atp1->kode_atp);

        // Uji form RPPM Create
        Livewire::test(\App\Filament\Staff\Resources\RppmResource\Pages\CreateRppm::class)
            ->set('data.capaian_pembelajaran_id', $cp1->id)
            ->assertSet('data.alur_tujuan_pembelajaran_id', $atp1->id)
            ->assertSee('[ATP 1]')
            ->assertSee('[CP 1]');
    }
}
