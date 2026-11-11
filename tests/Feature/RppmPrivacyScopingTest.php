<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\RppmResource;
use App\Models\Institution;
use App\Models\Rppm;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RppmPrivacyScopingTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guruA;
    protected User $guruB;
    protected User $wakaKurikulum;
    protected User $kepalaSekolah;
    protected Rppm $rppmGuruA;
    protected Rppm $rppmGuruB;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        $roles = ['superadmin', 'kepala_sekolah', 'waka_kurikulum', 'guru_mapel', 'guru_kelas'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        Institution::create([
            'id' => 1,
            'name' => 'Yayasan Baitul Makmur',
            'slug' => 'yayasan',
        ]);

        $this->institution = Institution::create([
            'id' => 2,
            'name' => 'SMP Baitul Makmur',
            'slug' => 'smp-baitul-makmur',
        ]);

        $this->guruA = User::create([
            'name' => 'Ustadzah Fatimah (Guru A)',
            'email' => 'guru_a@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->guruA->assignRole('guru_mapel');

        $this->guruB = User::create([
            'name' => 'Ustadz Ahmad (Guru B)',
            'email' => 'guru_b@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->guruB->assignRole('guru_mapel');

        $this->wakaKurikulum = User::create([
            'name' => 'Waka Kurikulum SMP',
            'email' => 'wakakur@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->wakaKurikulum->assignRole('waka_kurikulum');

        $this->kepalaSekolah = User::create([
            'name' => 'Kepala Sekolah SMP',
            'email' => 'kepsek@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        // Create RPPM for Guru A
        $this->rppmGuruA = Rppm::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guruA->id,
            'topik' => 'Aljabar dan Persamaan Linier (Milik Guru A)',
            'kelas_fase' => 'Kelas 7 / Fase D',
            'mata_pelajaran' => 'Matematika',
            'status' => 'published',
            'content' => ['tujuan' => 'Siswa memahami aljabar'],
        ]);

        // Create RPPM for Guru B
        $this->rppmGuruB = Rppm::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guruB->id,
            'topik' => 'Tata Surya dan Gravitasi (Milik Guru B)',
            'kelas_fase' => 'Kelas 7 / Fase D',
            'mata_pelajaran' => 'IPA',
            'status' => 'published',
            'content' => ['tujuan' => 'Siswa memahami tata surya'],
        ]);
    }

    public function test_guru_a_can_only_see_their_own_rppm_in_table()
    {
        $this->actingAs($this->guruA);

        Livewire::test(RppmResource\Pages\ListRppms::class)
            ->assertCanSeeTableRecords([$this->rppmGuruA])
            ->assertCanNotSeeTableRecords([$this->rppmGuruB]);
    }

    public function test_guru_b_can_only_see_their_own_rppm_in_table()
    {
        $this->actingAs($this->guruB);

        Livewire::test(RppmResource\Pages\ListRppms::class)
            ->assertCanSeeTableRecords([$this->rppmGuruB])
            ->assertCanNotSeeTableRecords([$this->rppmGuruA]);
    }

    public function test_waka_kurikulum_can_see_both_teachers_rppm_for_acc()
    {
        $this->actingAs($this->wakaKurikulum);

        Livewire::test(RppmResource\Pages\ListRppms::class)
            ->assertCanSeeTableRecords([$this->rppmGuruA, $this->rppmGuruB]);
    }

    public function test_kepala_sekolah_can_see_both_teachers_rppm_for_supervision()
    {
        $this->actingAs($this->kepalaSekolah);

        Livewire::test(RppmResource\Pages\ListRppms::class)
            ->assertCanSeeTableRecords([$this->rppmGuruA, $this->rppmGuruB]);
    }

    public function test_guru_cannot_edit_other_teachers_rppm()
    {
        $this->actingAs($this->guruA);

        // Guru A attempts to edit Guru B's RPPM -> should be 404 or 403 (resolved record is not accessible)
        $response = $this->get(RppmResource::getUrl('edit', ['record' => $this->rppmGuruB]));
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_guru_cannot_download_other_teachers_rppm_pdf()
    {
        $this->actingAs($this->guruA);

        // Guru A tries to download Guru B's RPPM PDF
        $response = $this->get(route('academic.rppm.pdf', ['rppm' => $this->rppmGuruB]));
        $response->assertStatus(403);

        // Guru A downloads their own RPPM PDF -> allowed (200)
        $responseOwn = $this->get(route('academic.rppm.pdf', ['rppm' => $this->rppmGuruA]));
        $responseOwn->assertStatus(200);
    }

    public function test_guru_can_delete_their_own_rppm(): void
    {
        $this->actingAs($this->guruA);

        Livewire::test(RppmResource\Pages\ListRppms::class)
            ->callTableAction('delete', $this->rppmGuruA);

        $this->assertDatabaseMissing('rppms', [
            'id' => $this->rppmGuruA->id,
        ]);
    }

    public function test_kepala_sekolah_cannot_delete_rppm(): void
    {
        $this->actingAs($this->kepalaSekolah);

        Livewire::test(RppmResource\Pages\ListRppms::class)
            ->assertTableActionHidden('delete', $this->rppmGuruA);
    }

    public function test_waka_kurikulum_can_delete_rppm(): void
    {
        $this->actingAs($this->wakaKurikulum);

        Livewire::test(RppmResource\Pages\ListRppms::class)
            ->assertTableActionVisible('delete', $this->rppmGuruA);
    }
}
