<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\SopResource;
use App\Models\Institution;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SopKepalaSekolahAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $kepalaSekolah;
    protected User $superadmin;
    protected User $wakaKurikulum;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->kepalaSekolah = User::factory()->create([
            'name' => 'Ustadz Kepala Sekolah',
            'email' => 'ks@smpit.test',
            'institution_id' => $this->institution->id,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        $this->superadmin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'admin@smpit.test',
            'institution_id' => $this->institution->id,
        ]);
        $this->superadmin->assignRole('superadmin');

        $this->wakaKurikulum = User::factory()->create([
            'name' => 'Ustadz Waka Kurikulum',
            'email' => 'wakakur@smpit.test',
            'institution_id' => $this->institution->id,
        ]);
        $this->wakaKurikulum->assignRole('waka_kurikulum');
    }

    public function test_sop_feature_is_disabled_for_kepala_sekolah(): void
    {
        $this->actingAs($this->kepalaSekolah);

        $this->assertFalse(
            SopResource::canAccess(),
            'Fitur SOP harus dinonaktifkan untuk role kepala_sekolah'
        );

        $this->assertFalse(
            SopResource::shouldRegisterNavigation(),
            'Menu SOP tidak boleh muncul di navigasi untuk role kepala_sekolah'
        );

        $this->assertFalse(
            SopResource::canViewAny(),
            'Kepala Sekolah tidak boleh memiliki izin canViewAny pada SopResource'
        );

        $this->assertFalse(
            SopResource::canCreate(),
            'Kepala Sekolah tidak boleh memiliki izin canCreate pada SopResource'
        );
    }

    public function test_sop_feature_remains_accessible_for_superadmin(): void
    {
        $this->actingAs($this->superadmin);

        $this->assertTrue(
            SopResource::canAccess(),
            'Superadmin tetap dapat mengakses SopResource'
        );

        $this->assertTrue(
            SopResource::shouldRegisterNavigation(),
            'Menu SOP harus tetap muncul di navigasi untuk superadmin'
        );
    }

    public function test_sop_feature_remains_accessible_for_waka_kurikulum(): void
    {
        $this->actingAs($this->wakaKurikulum);

        $this->assertTrue(
            SopResource::canAccess(),
            'Waka Kurikulum tetap dapat mengakses SopResource'
        );

        $this->assertTrue(
            SopResource::shouldRegisterNavigation(),
            'Menu SOP harus tetap muncul di navigasi untuk waka kurikulum'
        );
    }
}
