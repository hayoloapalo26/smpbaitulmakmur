<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\ProgramEvaluationResource;
use App\Models\Institution;
use App\Models\ProgramEvaluation;
use App\Models\ProgramKurikulum;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramSarpras;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProgramEvaluationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $kepsek;
    protected User $kurikulum;
    protected User $kesiswaan;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sarpras', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'humas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bk', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->kepsek = User::create([
            'name' => 'Kepala Sekolah',
            'username' => 'kepsek_user',
            'email' => 'kepsek@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->kepsek->assignRole('kepala_sekolah');

        $this->kurikulum = User::create([
            'name' => 'Waka Kurikulum',
            'username' => 'kurikulum_user',
            'email' => 'kurikulum@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->kurikulum->assignRole('waka_kurikulum');

        $this->kesiswaan = User::create([
            'name' => 'Waka Kesiswaan',
            'username' => 'kesiswaan_user',
            'email' => 'kesiswaan@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->kesiswaan->assignRole('waka_kesiswaan');
    }

    public function test_kepala_sekolah_sees_evaluasi_program_prominently(): void
    {
        $this->actingAs($this->kepsek);

        $this->assertTrue(ProgramEvaluationResource::canAccess());
        $this->assertEquals('Manajemen Sekolah', ProgramEvaluationResource::getNavigationGroup());
        $this->assertEquals(2, ProgramEvaluationResource::getNavigationSort());
        $this->assertEquals('Evaluasi Program', ProgramEvaluationResource::getNavigationLabel());
    }

    public function test_evaluasi_program_integrates_across_roles_and_kepsek_review(): void
    {
        // 1. Kurikulum membuat program kurikulum
        $prokerKurikulum = ProgramKurikulum::create([
            'institution_id' => $this->institution->id,
            'user_id' => $this->kurikulum->id,
            'nama_program' => 'Workshop Kurikulum Merdeka Terpadu',
            'kategori' => 'Pengembangan Kurikulum',
            'sifat' => 'Rutin',
            'tipe_pelaksanaan' => 'Sekolah',
            'waktu_pelaksanaan' => 'Agustus 2026',
            'target' => 'Seluruh Guru SMP',
            'status_pelaksanaan' => 'Selesai',
            'status_persetujuan' => 'Disetujui',
        ]);

        // 2. Kurikulum menginput evaluasi program
        $this->actingAs($this->kurikulum);
        $this->assertTrue(ProgramEvaluationResource::canAccess());
        $this->assertEquals('Akademik & Kurikulum', ProgramEvaluationResource::getNavigationGroup());

        $evaluasi = ProgramEvaluation::create([
            'institution_id' => $this->institution->id,
            'user_id' => $this->kurikulum->id,
            'divisi' => 'Kurikulum',
            'nama_program' => $prokerKurikulum->nama_program,
            'program_source_type' => ProgramKurikulum::class,
            'program_source_id' => $prokerKurikulum->id,
            'persentase_ketercapaian' => 95,
            'predikat' => 'Sangat Baik',
            'realisasi_kegiatan' => 'Terlaksana penuh dengan kehadiran 100% guru.',
            'status' => 'Menunggu Review KS',
        ]);

        $this->assertDatabaseHas('program_evaluations', [
            'id' => $evaluasi->id,
            'status' => 'Menunggu Review KS',
        ]);

        // 3. Kepala Sekolah meninjau (review) evaluasi tersebut
        $this->actingAs($this->kepsek);
        $this->assertTrue($this->kepsek->can('update', $evaluasi));

        $evaluasi->update([
            'status' => 'Disetujui KS',
            'catatan_kepsek' => 'Luar biasa, materi workshop telah diaplikasikan di kelas dengan baik.',
            'reviewed_by' => $this->kepsek->id,
            'tanggal_review' => now(),
        ]);

        $evaluasi->refresh();
        $this->assertEquals('Disetujui KS', $evaluasi->status);
        $this->assertEquals($this->kepsek->id, $evaluasi->reviewed_by);
        $this->assertStringContainsString('Luar biasa', $evaluasi->catatan_kepsek);
    }
}
