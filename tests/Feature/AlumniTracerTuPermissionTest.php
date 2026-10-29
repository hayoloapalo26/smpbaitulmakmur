<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\AlumniTracerResource;
use App\Models\AlumniTracer;
use App\Models\Institution;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlumniTracerTuPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $tuUser;
    protected User $wakaKesiswaanUser;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tata_usaha', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->tuUser = User::create([
            'name' => 'Staff Tata Usaha',
            'username' => 'staff_tu',
            'email' => 'tu@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->tuUser->assignRole('tata_usaha');

        $this->wakaKesiswaanUser = User::create([
            'name' => 'Waka Kesiswaan',
            'username' => 'waka_kesiswaan',
            'email' => 'kesiswaan@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->wakaKesiswaanUser->assignRole('waka_kesiswaan');
    }

    public function test_tata_usaha_has_full_permission_to_input_edit_and_delete_tracer_study(): void
    {
        $this->actingAs($this->tuUser);

        // Can access
        $this->assertTrue(AlumniTracerResource::canAccess());

        // Can create
        $this->assertTrue(AlumniTracerResource::canCreate());
        $this->assertTrue($this->tuUser->can('create', AlumniTracer::class));

        // Create sample record
        $tracer = AlumniTracer::create([
            'institution_id' => $this->institution->id,
            'nama' => 'Ahmad Alumni',
            'nis' => '12345',
            'tahun_lulus' => '2025',
            'status_kegiatan' => 'melanjutkan_pendidikan',
            'nama_instansi' => 'SMAN 1 Surabaya',
            'input_by' => $this->tuUser->id,
        ]);

        // Can edit & delete
        $this->assertTrue(AlumniTracerResource::canEdit($tracer));
        $this->assertTrue($this->tuUser->can('update', $tracer));
        $this->assertTrue(AlumniTracerResource::canDelete($tracer));
        $this->assertTrue($this->tuUser->can('delete', $tracer));

        // Navigation group for TU is 'Administrasi & Data Induk'
        $this->assertEquals('Administrasi & Data Induk', AlumniTracerResource::getNavigationGroup());
        $this->assertEquals(3, AlumniTracerResource::getNavigationSort());
    }

    public function test_waka_kesiswaan_can_view_tracer_study_but_not_input(): void
    {
        $this->actingAs($this->wakaKesiswaanUser);

        // Waka kesiswaan can view
        $this->assertTrue(AlumniTracerResource::canAccess());
        $this->assertTrue($this->wakaKesiswaanUser->can('viewAny', AlumniTracer::class));

        // Inputting is delegated to TU
        $this->assertFalse(AlumniTracerResource::canCreate());
        $this->assertFalse($this->wakaKesiswaanUser->can('create', AlumniTracer::class));

        $tracer = AlumniTracer::create([
            'institution_id' => $this->institution->id,
            'nama' => 'Budi Alumni',
            'nis' => '54321',
            'tahun_lulus' => '2025',
            'status_kegiatan' => 'belum_terlacak',
            'input_by' => $this->tuUser->id,
        ]);

        $this->assertTrue($this->wakaKesiswaanUser->can('view', $tracer));
        $this->assertFalse(AlumniTracerResource::canEdit($tracer));
        $this->assertFalse($this->wakaKesiswaanUser->can('update', $tracer));
    }
}
