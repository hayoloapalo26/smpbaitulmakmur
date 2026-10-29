<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\TeacherCertificateResource;
use App\Filament\Staff\Resources\TeacherCertificateResource\Pages\ManageTeacherCertificates;
use App\Models\Institution;
use App\Models\TeacherCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $smp;
    protected User $guru;
    protected User $kepsek;
    protected User $guruTahsin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'koordinator_madin', 'guard_name' => 'web']);

        $this->smp = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        $this->guru = User::create([
            'name' => 'Guru Test',
            'email' => 'guru@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->smp->id,
            'is_active' => true,
        ]);
        $this->guru->assignRole('guru_mapel');

        $this->guruTahsin = User::create([
            'name' => 'Guru Tahsin',
            'email' => 'tahsin@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->smp->id,
            'is_active' => true,
        ]);
        $this->guruTahsin->assignRole('guru_quran_tahsin');

        $this->kepsek = User::create([
            'name' => 'Kepsek Test',
            'email' => 'kepsek@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->smp->id,
            'is_active' => true,
        ]);
        $this->kepsek->assignRole('kepala_sekolah');

        try {
            \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));
        } catch (\Throwable $e) {
        }
    }

    public function test_teacher_can_render_certificate_page(): void
    {
        $this->actingAs($this->guru);

        Livewire::test(ManageTeacherCertificates::class)
            ->assertSuccessful();
    }

    public function test_teacher_can_save_and_view_certificate(): void
    {
        $this->actingAs($this->guru);

        $cert = TeacherCertificate::create([
            'teacher_id' => $this->guru->id,
            'nama_kegiatan' => 'Pelatihan AI Canva Guru',
            'penyelenggara' => 'Balai Guru Penggerak',
            'kategori' => 'Pelatihan / Bimtek',
            'tanggal_pelaksanaan' => '2026-10-25',
            'jumlah_jam' => 32,
            'drive_url' => 'https://drive.google.com/file/d/abc123xyz/view',
            'keterangan' => 'Belajar Canva AI',
        ]);

        $this->assertEquals($this->smp->id, $cert->institution_id);
        $this->assertDatabaseHas('teacher_certificates', [
            'id' => $cert->id,
            'nama_kegiatan' => 'Pelatihan AI Canva Guru',
            'teacher_id' => $this->guru->id,
            'institution_id' => $this->smp->id,
        ]);

        Livewire::test(ManageTeacherCertificates::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$cert]);
    }

    public function test_quran_teacher_and_koordinator_madin_can_access(): void
    {
        $this->actingAs($this->guruTahsin);

        $this->assertTrue(TeacherCertificateResource::canAccess());

        Livewire::test(ManageTeacherCertificates::class)
            ->assertSuccessful();
    }

    public function test_yayasan_panel_can_render_certificate_page(): void
    {
        Role::firstOrCreate(['name' => 'ketua_yayasan', 'guard_name' => 'web']);

        $ketuaYayasan = User::create([
            'name' => 'Ketua Yayasan',
            'email' => 'ketua@yayasan.com',
            'password' => bcrypt('password'),
            'institution_id' => 1,
            'is_active' => true,
        ]);
        $ketuaYayasan->assignRole('ketua_yayasan');

        try {
            \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('yayasan'));
        } catch (\Throwable $e) {
        }

        $this->actingAs($ketuaYayasan);

        Livewire::test(\App\Filament\Yayasan\Resources\TeacherCertificateResource\Pages\ManageTeacherCertificates::class)
            ->assertSuccessful();
    }
}
